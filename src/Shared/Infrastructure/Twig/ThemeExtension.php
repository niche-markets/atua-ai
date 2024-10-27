<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Twig;

use Easy\Container\Attributes\Inject;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;

class ThemeExtension extends AbstractExtension implements ExtensionInterface
{
    public function __construct(
        #[Inject('version')]
        private string $version,
        #[Inject('option.theme')]
        private string $theme = 'heyaikeedo/default',
    ) {
    }

    public function getFilters()
    {
        $funcs = [];

        // Custom functions
        $funcs[] = new TwigFilter(
            'asset_url',
            $this->getAssetUrl(...)
        );

        return $funcs;
    }

    private function getAssetUrl(
        string $asset,
    ): string {
        $asset = ltrim($asset, '/');
        $asset = '/content/plugins/' . $this->theme . '/assets/' . $asset . '?v=' . $this->version;

        return $asset;
    }
}
