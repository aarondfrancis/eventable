<?php

namespace AaronFrancis\Eventable;

use BadMethodCallException;
use DateTimeInterface;
use Illuminate\Support\Carbon;

final readonly class Prune
{
    private function __construct(
        private ?DateTimeInterface $before = null,
        private ?int $keep = null,
        private bool $varyOnData = true,
    ) {}

    public static function __callStatic(string $method, array $arguments): static
    {
        return (new self)->__call($method, $arguments);
    }

    public function __call(string $method, array $arguments): static
    {
        return match ($method) {
            'before' => $this->withBefore(...$arguments),
            'keep' => $this->withKeep(...$arguments),
            'varyOnData' => $this->withVaryOnData(...$arguments),
            'dontVaryOnData' => $this->withVaryOnData(false),
            default => throw new BadMethodCallException('Method ['.static::class."::{$method}] does not exist."),
        };
    }

    private function withBefore(DateTimeInterface $before): static
    {
        return new static(
            before: $before,
            keep: $this->keep,
            varyOnData: $this->varyOnData,
        );
    }

    private function withKeep(int $keep): static
    {
        if ($keep < 1) {
            throw new \InvalidArgumentException('PruneConfig keep must be at least 1.');
        }

        return new static(
            before: $this->before,
            keep: $keep,
            varyOnData: $this->varyOnData,
        );
    }

    private function withVaryOnData(bool $varyOnData = true): static
    {
        return new static(
            before: $this->before,
            keep: $this->keep,
            varyOnData: $varyOnData,
        );
    }

    public function toPruneConfig(): PruneConfig
    {
        return new PruneConfig(
            before: $this->before ? Carbon::instance($this->before) : null,
            keep: $this->keep,
            varyOnData: $this->varyOnData,
        );
    }
}
