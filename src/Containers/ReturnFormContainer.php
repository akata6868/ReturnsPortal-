<?php

namespace ReturnsPortal\Containers;

use Plenty\Plugin\Templates\Twig;

class ReturnFormContainer
{
    public function call(Twig $twig): string
    {
        return $twig->render('ReturnsPortal::content.return-form');
    }
}
