<?php

namespace ReturnsPortal\Containers;

use Plenty\Plugin\Templates\Twig;

class ReturnTrackingContainer
{
    public function call(Twig $twig, $returnId = null): string
    {
        return $twig->render('ReturnsPortal::content.return-tracking', [
            'returnId' => $returnId
        ]);
    }
}
