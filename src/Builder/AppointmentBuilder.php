<?php

namespace Nzm\Appointment\Builder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nzm\Appointment\Models\Appointment;

class AppointmentBuilder
{
    protected $agentable;

    protected $clientable = null;

    protected $startTime;

    protected $endTime = null;

    protected ?int $duration = null;

    protected ?int $count = null;

    protected ?string $note = null;

    public function setAgent($agentable): static
    {
        $this->agentable = $agentable;

        return $this;
    }

    public function setClient($clientable = null): static
    {
        $this->clientable = $clientable;

        return $this;
    }

    public function startTime($startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function endTime($endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function duration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function count(?int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function note(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    protected function createAppointment(array $data): Appointment
    {
        return Appointment::query()->create($data);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(): void
    {
        $validationData = [
            'agentable_id' => $this->agentable?->id,
            'agentable_type' => get_class($this->agentable),
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'count' => $this->count,
            'duration' => $this->duration,
            'note' => $this->note,
        ];

        $validationRules = [
            'agentable_id' => 'required|integer|exists:'.$this->agentable->getTable().',id',
            'agentable_type' => 'required|string',
            'start_time' => [
                'required',
                'date_format:Y-m-d H:i',
                'before:end_time',
                function ($attribute, $value, $fail) {
                    // Existing duplicate check
                    if (! config('appointment.duplicate', false)) {
                        $query = Appointment::query()
                            ->where('agentable_id', $this->agentable->id)
                            ->where('agentable_type', get_class($this->agentable))
                            ->where('start_time', $value);

                        // Only check for existing appointment if a client is set
                        if ($this->clientable) {
                            $query->where('clientable_id', $this->clientable->id)
                                ->where('clientable_type', get_class($this->clientable));
                        }

                        $exists = $query->exists();

                        if ($exists) {
                            $fail('An appointment at this time already exists.');
                        }
                    }

                    // New overlap validation
                    $overlapQuery = Appointment::query()
                        ->where('agentable_id', $this->agentable->id)
                        ->where('agentable_type', get_class($this->agentable))
                        ->where(function ($query) use ($value) {
                            // Check if new appointment's start time is within an existing appointment's time range
                            $query->where(function ($subQuery) use ($value) {
                                $subQuery->where('start_time', '<=', $value)
                                    ->where('end_time', '>', $value);
                            });
                        });

                    // Only check for existing appointment if a client is set
                    if ($this->clientable) {
                        $overlapQuery->where('clientable_id', $this->clientable->id)
                            ->where('clientable_type', get_class($this->clientable));
                    }

                    $hasOverlap = $overlapQuery->exists();

                    if ($hasOverlap) {
                        $fail('This appointment conflicts with an existing appointment time.');
                    }
                },
            ],
            'end_time' => ['nullable', 'required_without:duration,count', 'date_format:Y-m-d H:i', 'after:start_time'],
            'count' => ['nullable', 'integer', 'min:1', 'required_with:duration'],
            'duration' => ['nullable', 'integer', 'min:1', 'required_with:count'],
            'note' => ['nullable', 'string'],
        ];

        // Add optional client validation if client is set
        if ($this->clientable) {
            $validationData['clientable_id'] = $this->clientable->id;
            $validationData['clientable_type'] = get_class($this->clientable);

            $validationRules['clientable_id'] = 'sometimes|integer|exists:'.$this->clientable->getTable().',id';
            $validationRules['clientable_type'] = 'sometimes|string';
        }

        $validator = Validator::make($validationData, $validationRules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @throws ValidationException
     */
    public function save(): array|Appointment
    {
        $this->validate();

        // If duration and count are set, create multiple appointments
        if ($this->duration && $this->count) {
            return $this->createMultipleAppointments();
        }

        // Otherwise, create a single appointment
        return $this->createSingleAppointment();
    }

    private function createMultipleAppointments(): array
    {
        $appointments = [];

        return DB::transaction(function () use (&$appointments) {
            $currentStartTime = $this->startTime;

            for ($i = 0; $i < $this->count; $i++) {
                // Calculate end time based on the current start time
                $endTime = now()->parse($currentStartTime)
                    ->addMinutes($this->duration)
                    ->format('Y-m-d H:i');

                $appointments[] = $this->createAppointment(
                    $this->prepareAppointmentData($currentStartTime, $endTime)
                );

                // Update current start time for next iteration
                $currentStartTime = $endTime;
            }

            return $appointments;
        });
    }

    private function createSingleAppointment(): Appointment
    {
        return DB::transaction(function () {
            return $this->createAppointment(
                $this->prepareAppointmentData($this->startTime, $this->endTime)
            );
        });
    }

    private function prepareAppointmentData(string $startTime, string $endTime): array
    {
        $appointmentData = [
            'agentable_id' => $this->agentable->id,
            'agentable_type' => get_class($this->agentable),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'note' => $this->note,
        ];

        // Add client data only if clientable is set
        if ($this->clientable) {
            $appointmentData['clientable_id'] = $this->clientable->id;
            $appointmentData['clientable_type'] = get_class($this->clientable);
        }

        return $appointmentData;
    }
}
