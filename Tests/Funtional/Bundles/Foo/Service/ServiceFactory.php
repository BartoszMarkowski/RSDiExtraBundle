<?php
namespace RS\DiExtraBundle\Tests\Funtional\Bundles\Foo\Service;

use RS\DiExtraBundle\Annotation\Inject;
use RS\DiExtraBundle\Annotation\InjectParams;
use RS\DiExtraBundle\Annotation\Service;
use RS\DiExtraBundle\Annotation\Tag;

/**
 * @Service()
 */
class ServiceFactory
{
    protected $params = array();

    /**
     * @InjectParams({
     *     "foo" = @Inject("%foo%"),
     * })
     */
    public function __construct(FooNotPublicService $fooNotPublicService, $fooNotPublic, $foo)
    {
        $this->params = array(
            'fooNotPublicService' => $fooNotPublicService,
            'fooNotPublic' => $fooNotPublic,
            'foo' => $foo,
        );
    }

    /**
     *
     * @Service("foo_service_factory", class=\stdClass::class)
     * @Tag("foo_tag", attributes={"foo": "foo"})
     */
    public function create()
    {
        $object = new \stdClass();
        $object->params = $this->params;
        $object->params['id'] = 'foo_service_factory';
        return $object;
    }
}
