<?php


namespace Atom\Framework\Contracts;

use Atom\Validation\Validator;
use Atom\Framework\Http\Request;
use Psr\Http\Message\ServerRequestInterface;

interface ValidatedRequestContract extends ServerRequestInterface
{
    public function setRules(Validator $validator);

    public function fillWith(Request $validator): ValidatedRequestContract;
}
