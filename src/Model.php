<?php

namespace Digitlimit\Rediloquent;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class Model
{
    /**
     * The Redis key segment that identifies records of this model type.
     * Defaults to the snake_case class name when left empty.
     */
    protected string $key = '';

    /**
     * The attributes that are mass assignable.
     * An empty array allows all attributes to be filled.
     */
    protected array $fillable = [];

    /**
     * The model's underlying attribute store.
     */
    protected array $attributes = [];

    /**
     * Indicates whether the model currently exists in Redis.
     */
    protected bool $exists = false;

    /**
     * The unique identifier for this record.
     */
    public string|int|null $id = null;

    /**
     * The ISO-8601 timestamp when the record was created.
     */
    public ?string $created_at = null;

    /**
     * The ISO-8601 timestamp when the record was last updated.
     */
    public ?string $updated_at = null;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    // -------------------------------------------------------------------------
    // Mass assignment
    // -------------------------------------------------------------------------

    /**
     * Fill the model with an array of attributes.
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $attr => $value) {
            if (empty($this->fillable) || in_array($attr, $this->fillable, true)) {
                $this->attributes[$attr] = $value;
            }
        }

        return $this;
    }

    // -------------------------------------------------------------------------
    // Magic property access
    // -------------------------------------------------------------------------

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    // -------------------------------------------------------------------------
    // Redis helpers
    // -------------------------------------------------------------------------

    /**
     * Return the configured Redis connection.
     */
    protected static function redisConnection(): \Illuminate\Redis\Connections\Connection
    {
        /** @var string $connection */
        $connection = config('rediloquent.connection', 'default');

        return Redis::connection($connection);
    }

    /**
     * Return the global key prefix.
     */
    protected static function keyPrefix(): string
    {
        return (string) config('rediloquent.prefix', 'rediloquent');
    }

    /**
     * Return the model-level key segment (e.g. "users").
     */
    protected function modelSegment(): string
    {
        if (!empty($this->key)) {
            return $this->key;
        }

        return Str::snake(class_basename(static::class));
    }

    /**
     * Static shortcut for modelSegment().
     */
    protected static function getModelSegment(): string
    {
        return (new static())->modelSegment();
    }

    /**
     * Redis key for a single record: {prefix}:{modelSegment}:{id}
     */
    protected static function recordKey(string|int $id): string
    {
        return static::keyPrefix() . ':' . static::getModelSegment() . ':' . $id;
    }

    /**
     * Redis key for the ID index set: {prefix}:{modelSegment}:ids
     */
    protected static function indexKey(): string
    {
        return static::keyPrefix() . ':' . static::getModelSegment() . ':ids';
    }

    // -------------------------------------------------------------------------
    // CRUD – static factory methods
    // -------------------------------------------------------------------------

    /**
     * Persist a new record in Redis and return the populated model instance.
     */
    public static function create(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->id = (string) Str::uuid();
        $model->created_at = now()->toIso8601String();
        $model->updated_at = now()->toIso8601String();
        $model->exists = true;
        $model->persistToRedis();

        return $model;
    }

    /**
     * Retrieve a record by its ID. Returns null when the key does not exist.
     */
    public static function find(string|int $id): ?static
    {
        $redis = static::redisConnection();
        $raw = $redis->get(static::recordKey($id));

        if ($raw === null || $raw === false) {
            return null;
        }

        $data = json_decode((string) $raw, true);

        if (!is_array($data)) {
            return null;
        }

        $model = new static();
        $model->id = $data['id'] ?? $id;
        $model->created_at = $data['created_at'] ?? null;
        $model->updated_at = $data['updated_at'] ?? null;

        unset($data['id'], $data['created_at'], $data['updated_at']);
        $model->attributes = $data;
        $model->exists = true;

        return $model;
    }

    /**
     * Retrieve all records for this model.
     *
     * @return static[]
     */
    public static function all(): array
    {
        $redis = static::redisConnection();
        $ids = $redis->smembers(static::indexKey());

        if (empty($ids)) {
            return [];
        }

        $models = [];

        foreach ($ids as $id) {
            $model = static::find($id);

            if ($model !== null) {
                $models[] = $model;
            }
        }

        return $models;
    }

    // -------------------------------------------------------------------------
    // CRUD – instance methods
    // -------------------------------------------------------------------------

    /**
     * Update the model's attributes and persist the changes.
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        $this->updated_at = now()->toIso8601String();

        return $this->persistToRedis();
    }

    /**
     * Remove the record from Redis.
     */
    public function delete(): bool
    {
        if (!$this->exists || $this->id === null) {
            return false;
        }

        $redis = static::redisConnection();
        $redis->del(static::recordKey($this->id));
        $redis->srem(static::indexKey(), (string) $this->id);
        $this->exists = false;

        return true;
    }

    /**
     * Persist the current state of the model to Redis.
     * Assigns an ID and created_at timestamp when the model is new.
     */
    public function save(): bool
    {
        if ($this->id === null) {
            $this->id = (string) Str::uuid();
            $this->created_at = now()->toIso8601String();
        }

        $this->updated_at = now()->toIso8601String();
        $this->exists = true;

        return $this->persistToRedis();
    }

    // -------------------------------------------------------------------------
    // Serialisation helpers
    // -------------------------------------------------------------------------

    /**
     * Encode the model and write it to Redis.
     */
    protected function persistToRedis(): bool
    {
        $redis = static::redisConnection();

        $payload = array_merge($this->attributes, [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $redis->set(static::recordKey($this->id), json_encode($payload));
        $redis->sadd(static::indexKey(), (string) $this->id);

        return true;
    }

    /**
     * Return the model's attributes as an associative array.
     */
    public function toArray(): array
    {
        return array_merge($this->attributes, [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
