<?php

namespace Nzm\Appointment\Builder;

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
        ];

        $validationRules = [
            'agentable_id' => 'required|integer|exists:'.$this->agentable->getTable().',id',
            'agentable_type' => 'required|string',
            'start_time' => [
                'required',
                'date_format:Y-m-d H:i',
                'before:end_time',
                function ($attribute, $value, $fail) {
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
                },
            ],
            'end_time' => ['nullable', 'required_without:duration,count', 'date_format:Y-m-d H:i', 'after:start_time'],
            'count' => ['nullable', 'integer', 'min:1', 'required_with:duration'],
            'duration' => ['nullable', 'integer', 'min:1', 'required_with:count'],
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

        $appointments = [];

        if ($this->duration && $this->count) {
            for ($i = 0; $i < $this->count; $i++) {
                //convert start time string to Carbon instance
                $this->endTime = now()->parse($this->startTime)->addMinutes($this->duration)->format('Y-m-d H:i');

                $appointmentData = [
                    'agentable_id' => $this->agentable->id,
                    'agentable_type' => get_class($this->agentable),
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                ];

                // Add client data only if clientable is set
                if ($this->clientable) {
                    $appointmentData['clientable_id'] = $this->clientable->id;
                    $appointmentData['clientable_type'] = get_class($this->clientable);
                }

                $appointments[] = $this->createAppointment($appointmentData);

                $this->startTime = $this->endTime;
            }

            return $appointments;
        }

        $appointmentData = [
            'agentable_id' => $this->agentable->id,
            'agentable_type' => get_class($this->agentable),
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];

        // Add client data only if clientable is set
        if ($this->clientable) {
            $appointmentData['clientable_id'] = $this->clientable->id;
            $appointmentData['clientable_type'] = get_class($this->clientable);
        }

        return $this->createAppointment($appointmentData);
    }
}
