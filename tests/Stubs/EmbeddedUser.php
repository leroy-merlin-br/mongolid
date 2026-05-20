<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;
use Mongolid\Model\Relations\EmbedsMany;
use Mongolid\Model\Relations\EmbedsOne;

class EmbeddedUser extends AbstractModel
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $collection = 'users';

    /**
     * @var bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $timestamps = true;

    public function parent(): EmbedsOne
    {
        return $this->embedsOne(EmbeddedUser::class);
    }

    public function siblings(): EmbedsMany
    {
        return $this->embedsMany(EmbeddedUser::class);
    }

    public function son(): EmbedsOne
    {
        return $this->embedsOne(EmbeddedUser::class, 'arbitrary_field');
    }

    public function grandsons(): EmbedsMany
    {
        return $this->embedsMany(EmbeddedUser::class, 'other_arbitrary_field');
    }

    public function sameName(): EmbedsOne
    {
        return $this->embedsOne(EmbeddedUser::class, 'sameName');
    }
}
