<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Adapters;

use Closure;
use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;

class ExtendedManipulator extends StringsManipulators
{
    public function apply(Closure|callable $callable): static
    {
        $value = call_user_func($callable, $this->value);
        return new static($value);
    }
}