<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class PackVariantInput
{
    /** @param array<string, string> $attributes @param array<int, PackVariantMappingInput> $componentMappings */
    public function __construct(
        public ?int $id,
        public array $attributes,
        public bool $isDefault,
        public bool $isActive,
        public array $componentMappings,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            attributes: $data['attributes'] ?? [],
            isDefault: (bool) ($data['is_default'] ?? false),
            isActive: (bool) ($data['is_active'] ?? true),
            componentMappings: array_map(PackVariantMappingInput::from(...), $data['component_mappings'] ?? []),
        );
    }
}
