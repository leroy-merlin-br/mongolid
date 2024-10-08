<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;
use Mongolid\Model\Relations\EmbedsMany;
use Mongolid\Model\Relations\EmbedsOne;

class EmbeddedUser extends AbstractModel
{
    protected ?string $collection = 'users';

    protected bool $timestamps = true;

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

    public function sameName(): void
    {
        $this->embedsOne(EmbeddedUser::class, 'sameName');
    }
}
