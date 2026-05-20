<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;
use Mongolid\Model\Relations\ReferencesMany;
use Mongolid\Model\Relations\ReferencesOne;
use Mongolid\Model\PolymorphableModelInterface;

class ReferencedUser extends AbstractModel implements PolymorphableModelInterface
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
    protected $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'type',
        'exclusive',
        'other_exclusive',
    ];

    public function parent(): ReferencesOne
    {
        return $this->referencesOne(ReferencedUser::class);
    }

    public function siblings(): ReferencesMany
    {
        return $this->referencesMany(ReferencedUser::class);
    }

    public function son(): ReferencesOne
    {
        return $this->referencesOne(ReferencedUser::class, 'arbitrary_field', 'code');
    }

    public function grandsons(): ReferencesMany
    {
        return $this->referencesMany(ReferencedUser::class, null, 'code');
    }

    public function invalid(): string
    {
        return 'I am not a relation!';
    }

    /**
     * {@inheritdoc}
     */
    /**
     * @param array<string, mixed> $input
     *
     * @return class-string<ReferencedUser>
     */
    public function polymorph(array $input): string
    {
        if ('polymorphed' === ($input['type'] ?? '')) {
            return PolymorphedReferencedUser::class;
        }

        return static::class;
    }
}
