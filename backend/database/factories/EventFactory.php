<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Event types with their relative weights.
     */
    protected array $eventTypes = [
        'session_start' => 15,
        'app_open' => 15,
        'screen_view' => 20,
        'button_click' => 15,
        'purchase' => 5,
        'signup' => 3,
        'tutorial_complete' => 7,
        'feature_used' => 10,
        'settings_changed' => 5,
        'share_content' => 5,
    ];

    /**
     * iPhone models with their relative popularity.
     */
    protected array $deviceModels = [
        'iPhone 15 Pro Max' => 15,
        'iPhone 15 Pro' => 15,
        'iPhone 15' => 12,
        'iPhone 14 Pro Max' => 10,
        'iPhone 14 Pro' => 10,
        'iPhone 14' => 8,
        'iPhone 13 Pro' => 8,
        'iPhone 13' => 7,
        'iPhone 12' => 5,
        'iPhone SE (3rd gen)' => 5,
        'iPhone 11' => 5,
    ];

    /**
     * iOS versions with their relative distribution.
     */
    protected array $osVersions = [
        '17.2' => 25,
        '17.1.2' => 20,
        '17.1.1' => 15,
        '17.0' => 10,
        '16.7.2' => 10,
        '16.6' => 8,
        '16.5' => 7,
        '16.4' => 5,
    ];

    /**
     * App versions with their relative distribution.
     */
    protected array $appVersions = [
        '1.2.0' => 40,
        '1.1.5' => 25,
        '1.1.0' => 20,
        '1.0.5' => 10,
        '1.0.0' => 5,
    ];

    /**
     * Country codes with their relative distribution.
     */
    protected array $countryCodes = [
        'US' => 35,
        'GB' => 12,
        'CA' => 10,
        'AU' => 8,
        'DE' => 8,
        'FR' => 7,
        'JP' => 6,
        'IN' => 5,
        'BR' => 5,
        'NL' => 4,
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'event_id' => (string) Str::uuid(),
            'event_name' => $this->weightedRandom($this->eventTypes),
            'user_id' => 'user_' . fake()->unique()->regexify('[A-Za-z0-9]{12}'),
            'device_id' => 'device_' . fake()->regexify('[A-Za-z0-9]{16}'),
            'session_id' => 'session_' . fake()->regexify('[A-Za-z0-9]{20}'),
            'properties' => $this->generateProperties(),
            'os_version' => $this->weightedRandom($this->osVersions),
            'device_model' => $this->weightedRandom($this->deviceModels),
            'app_version' => $this->weightedRandom($this->appVersions),
            'country_code' => $this->weightedRandom($this->countryCodes),
            'event_timestamp' => fake()->dateTimeBetween('-30 days', 'now'),
            'received_at' => now(),
        ];
    }

    /**
     * Pick a random value based on weights.
     */
    protected function weightedRandom(array $items): string
    {
        $totalWeight = array_sum($items);
        $random = mt_rand(1, $totalWeight);

        foreach ($items as $value => $weight) {
            $random -= $weight;
            if ($random <= 0) {
                return (string) $value;
            }
        }

        return (string) array_key_first($items);
    }

    /**
     * Generate event-specific properties.
     */
    protected function generateProperties(): array
    {
        return [
            'screen_name' => fake()->randomElement(['Home', 'Profile', 'Settings', 'Feed', 'Search', 'Detail']),
            'source' => fake()->randomElement(['organic', 'push', 'deeplink', 'share']),
        ];
    }

    /**
     * Set a specific event type.
     */
    public function ofType(string $eventName): static
    {
        return $this->state(fn(array $attributes) => [
            'event_name' => $eventName,
        ]);
    }

    /**
     * Set the event timestamp.
     */
    public function at(\DateTimeInterface $timestamp): static
    {
        return $this->state(fn(array $attributes) => [
            'event_timestamp' => $timestamp,
            'received_at' => $timestamp,
        ]);
    }

    /**
     * Set a specific user.
     */
    public function forUser(string $userId): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Set a specific session.
     */
    public function forSession(string $sessionId): static
    {
        return $this->state(fn(array $attributes) => [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Create a non-activation event (session_start or app_open).
     */
    public function nonActivation(): static
    {
        return $this->state(fn(array $attributes) => [
            'event_name' => fake()->randomElement(['session_start', 'app_open']),
        ]);
    }

    /**
     * Create an activation event (any meaningful action).
     */
    public function activation(): static
    {
        $activationEvents = array_diff(
            array_keys($this->eventTypes),
            ['session_start', 'app_open']
        );

        return $this->state(fn(array $attributes) => [
            'event_name' => fake()->randomElement($activationEvents),
        ]);
    }
}
