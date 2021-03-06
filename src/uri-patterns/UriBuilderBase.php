<?hh //strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\HackRouter;

abstract class UriBuilderBase {
  protected ImmVector<UriPatternPart> $parts;
  protected ImmMap<string, RequestParameter> $parameters;
  private Map<string, string> $values = Map { };

  public function __construct(
    Traversable<UriPatternPart> $parts,
  ) {
    $this->parts = new ImmVector($parts);
    $parameters = Map { };
    foreach ($parts as $part) {
      if (!$part instanceof RequestParameter) {
        continue;
      }
      $parameters[$part->getName()] = $part;
    }
    $this->parameters = $parameters->immutable();
  }

  final protected function getPathImpl(): string {
    $uri = '';
    foreach ($this->parts as $part) {
      if ($part instanceof UriPatternLiteral) {
        $uri .= $part->getValue();
        continue;
      }

      invariant(
        $part instanceof RequestParameter,
        'expecting all UriPatternParts to be literals or parameters, got %s',
        get_class($part),
      );

      if ($uri === '') {
        $uri = '/';
      }

      $name = $part->getName();
      invariant(
        $this->values->containsKey($name),
        'Parameter "%s" must be set',
        $name,
      );
      $uri .= $this->values->at($name);
    }
    invariant(
      substr($uri, 0, 1) === '/',
      "Path '%s' does not start with '/'",
      $uri,
    );
    return $uri;
  }

  final protected function setValue<T>(
    classname<TypedUriParameter<T>> $parameter_type,
    string $name,
    T $value,
  ): this {
    $part = $this->parameters[$name] ?? null;
    invariant(
      $part !== null,
      "%s is not a valid parameter - expected one of [%s]",
      $name,
      implode(
        ', ',
        $this->parameters->keys()->map($x ==> "'".$x."'"),
      ),
    );
    invariant(
      $part instanceof $parameter_type,
      'Expected %s to be a %s, got a %s',
      $name,
      $parameter_type,
      get_class($part),
    );
    invariant(
      !$this->values->containsKey($name),
      'trying to set %s twice',
      $name,
    );
    $this->values[$name] = $part->getUriFragment($value);
    return $this;
  }
}
