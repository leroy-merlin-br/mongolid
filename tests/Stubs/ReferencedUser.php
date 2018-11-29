<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;
use Mongolid\Model\PolymorphableModelInterface;

class ReferencedUser extends AbstractModel implements PolymorphableModelInterface
{
    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var bool
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

    public function parent()
    {
        return $this->referencesOne(ReferencedUser::class);
    }

    public function siblings()
    {
        return $this->referencesMany(ReferencedUser::class);
    }

    public function son()
    {
        return $this->referencesOne(ReferencedUser::class, 'arbitrary_field', 'code');
    }

    public function grandsons()
    {
        return $this->referencesMany(ReferencedUser::class, null, 'code');
    }

    public function invalid()
    {
        return 'I am not a relation!';
    }

    /**
     * {@inheritdoc}
     */
    public function polymorph(array $input): string
    {
        if ('polymorphed' === ($input['type'] ?? '')) {
            return PolymorphedReferencedUser::class;
        }

        return static::class;
    }
}
