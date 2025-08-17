<?php

namespace Nazemi\Laraserve\Builder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nazemi\Laraserve\Models\Reservation;

class ReservationBuilder
{
    protected $provider;

    protected $recipient = null;

    protected $startTime;

    protected $endTime = null;

    protected ?int $duration = null;

    protected ?int $count = null;

    protected ?string $note = null;

    public function setProvider($provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function setRecipient($recipient = null): static
    {
        $this->recipient = $recipient;

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

    protected function createReservation(array $data): Reservation
    {
        return Reservation::query()->create($data);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(): void
    {
        $validationData = [
            'provider_id' => $this->provider?->id,
            'provider_type' => get_class($this->provider),
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'count' => $this->count,
            'duration' => $this->duration,
            'note' => $this->note,
        ];

        $validationRules = [
            'provider_id' => [
                'required',
                'integer',
                'exists:' . $this->provider->getTable() . ',id',
            ],
            'provider_type' => ['required', 'string'],
            'start_time' => [
                'required',
                'date_format:Y-m-d H:i',
                'before:end_time',
                $this->getStartTimeValidationCallback(),
            ],
            'end_time' => [
                'nullable',
                'required_without:duration,count',
                'date_format:Y-m-d H:i',
                'after:start_time',
            ],
            'count' => ['nullable', 'integer', 'min:1', 'required_with:duration'],
            'duration' => ['nullable', 'integer', 'min:1', 'required_with:count'],
            'note' => ['nullable', 'string'],
        ];

        // Add optional recipient validation if recipient is set
        if ($this->recipient) {
            $validationData['recipient_id'] = $this->recipient->id;
            $validationData['recipient_type'] = get_class($this->recipient);

            $validationRules['recipient_id'] = ['sometimes','integer','exists:'.$this->recipient->getTable().',id'];
            $validationRules['recipient_type'] = ['sometimes','string'];
        }

        $validator = Validator::make($validationData, $validationRules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @throws ValidationException
     */
    public function save(): array|Reservation
    {
        $this->validate();

        // If duration and count are set, create multiple reservations
        if ($this->duration && $this->count) {
            return $this->createMultipleReservations();
        }

        // Otherwise, create a single reservation
        return $this->createSingleReservation();
    }

    private function createMultipleReservations(): array
    {
        $reservations = [];

        return DB::transaction(function () use (&$reservations) {
            $currentStartTime = $this->startTime;

            for ($i = 0; $i < $this->count; $i++) {
                // Calculate end time based on the current start time
                $endTime = now()->parse($currentStartTime)
                    ->addMinutes($this->duration)
                    ->format('Y-m-d H:i');

                $reservations[] = $this->createReservation(
                    $this->prepareReservationData($currentStartTime, $endTime)
                );

                // Update current start time for next iteration
                $currentStartTime = $endTime;
            }

            return $reservations;
        });
    }

    private function createSingleReservation(): Reservation
    {
        return DB::transaction(function () {
            return $this->createReservation(
                $this->prepareReservationData($this->startTime, $this->endTime)
            );
        });
    }

    private function prepareReservationData(string $startTime, string $endTime): array
    {
        $reservationData = [
            'provider_id' => $this->provider->id,
            'provider_type' => get_class($this->provider),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'note' => $this->note,
        ];

        // Add recipient data only if recipient is set
        if ($this->recipient) {
            $reservationData['recipient_id'] = $this->recipient->id;
            $reservationData['recipient_type'] = get_class($this->recipient);
        }

        return $reservationData;
    }

    private function getStartTimeValidationCallback(): callable
    {
        return function ($attribute, $value, $fail) {
            if (config('laraserve.overlap', true))
            {
                $this->validateNoTimeOverlap($value, $fail);
            }
        };
    }

    private function validateNoTimeOverlap($value, $fail): void
    {
        $overlapQuery = Reservation::query()
            ->where('provider_id', $this->provider->id)
            ->where('provider_type', get_class($this->provider))
            ->where(function ($query) use ($value) {
                $query->where('start_time', '<=', $value)
                    ->where('end_time', '>', $value);
            });

        if ($this->recipient)
        {
            $overlapQuery->where('recipient_id', $this->recipient->id)
                ->where('recipient_type', get_class($this->recipient));
        }

        if ($overlapQuery->exists())
        {
            $fail('This reservation conflicts with an existing reservation time.');
        }
    }
}
