<?php

namespace App\Models\Concerns;

/**
 * Photo editing services a package can offer. Shared by studio and
 * freelancer packages.
 */
trait HasEditingOptions
{
    /** Service key => label shown to clients. */
    public const EDITING_SERVICES = [
        'color_grading' => 'Color grading',
        'brightness_contrast' => 'Brightness and contrast',
        'retouching' => 'Retouching',
    ];

    /**
     * The services this package offers, with prices. A package made before
     * per-service prices existed offers its single enhanced-photos price.
     *
     * @return array<string, array{label: string, price: float}>
     */
    public function editingOptions(): array
    {
        $options = [];
        foreach (self::EDITING_SERVICES as $key => $label) {
            $price = $this->editing_prices[$key] ?? null;
            if ($price !== null && $price !== '') {
                $options[$key] = ['label' => $label, 'price' => (float) $price];
            }
        }

        if ($options === [] && $this->enhanced_photos_price !== null) {
            $options['enhanced'] = ['label' => 'Enhanced photos', 'price' => (float) $this->enhanced_photos_price];
        }

        return $options;
    }
}
