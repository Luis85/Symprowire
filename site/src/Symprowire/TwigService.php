<?php


namespace App\Symprowire;


use ProcessWire\Wire;
use ProcessWire\WireException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class TwigService extends Wire
{

    protected Environment $twig;

    /**
     * @throws WireException
     */
    public function setEnvironment() {

        $paths = $this->wire('config')->paths;

        $loader = new FilesystemLoader($paths->templates.'twig/', );
        $twig = new Environment($loader, [
            'cache' => $paths->site.'cache/',
            'debug' => $this->wire('config')->debug,
        ]);
        $this->twig = $twig;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function renderTwigTemplate(string $template, array $values = []): string {
        return $this->twig->render($template.'.html.twig', $values);
    }
}
