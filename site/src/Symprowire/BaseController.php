<?php


namespace App\Symprowire;


use ProcessWire\Wire;
use ProcessWire\Wire404Exception;
use ProcessWire\WireException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends Wire
{

    protected TwigService $twig;

    protected $formFactory;

    /**
     * @throws WireException
     */
    public function __construct() {
        parent::__construct();

        $twig = new TwigService();
        $twig->setEnvironment();
        $this->twig = $twig;

        $this->formFactory = $this->wire('formfactory');
    }

    /**
     * @throws Wire404Exception
     */
    public function render(string $template, array $values = [], int $status = 200): Response {
        try{
            $markup = $this->twig->renderTwigTemplate($template, $values);
        } catch (\Exception $e) {
            throw new Wire404Exception($e->getMessage());
        }
        return new Response($markup,  $status);
    }

    protected function json($data, int $status = 200, array $headers = []): JsonResponse {
        return new JsonResponse($data, $status, $headers);
    }

}
