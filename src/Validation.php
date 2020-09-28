<?php


namespace Atom\Web;

use Atom\App\App;
use Atom\App\Contracts\ServiceProviderContract;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\DI\Exceptions\UnsupportedInvokerException;
use Atom\Validation\Translation\TranslationBag;
use Atom\Validation\Validator;
use Exception;

class Validation implements ServiceProviderContract
{
    /**
     * @var TranslationBag|null
     */
    private $translation;

    /**
     * Validation constructor.
     * @param null $translation
     * @throws Exception
     */
    public function __construct($translation = null)
    {
        if (is_array($translation)) {
            $this->translation = new TranslationBag($translation);
        } elseif ($translation instanceof TranslationBag) {
            $this->translation = $translation;
        } else {
            throw new Exception("translations should be either a " . TranslationBag::class . " object or an array");
        }
    }

    /**
     * @param App $app
     * @throws StorageNotFoundException
     * @throws UnsupportedInvokerException
     */
    public function register(App $app)
    {
        $c = $app->container();
        $c->factories()
            ->store(
                Validator::class,
                $c->as()->callTo("makeValidator")->method()->on($this)
            );
    }

    public function makeValidator(): Validator
    {
        return new Validator($this->translation);
    }
}
