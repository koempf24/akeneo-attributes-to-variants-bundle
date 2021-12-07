<?php
declare(strict_types = 1);

namespace Koempf\AttributeToVariantsBundle\Adder;

use Akeneo\Pim\Structure\Bundle\Doctrine\ORM\Saver\FamilySaver;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Model\FamilyInterface;

class AttributeToVariantsAdder
{
    private FamilySaver $familySaver;

    /**
     * @param \Akeneo\Pim\Structure\Bundle\Doctrine\ORM\Saver\FamilySaver $familySaver
     */
    public function __construct(FamilySaver $familySaver)
    {
        $this->familySaver = $familySaver;
    }

    /**
     * @param \Akeneo\Pim\Structure\Component\Model\AttributeInterface $attribute
     * @param \Akeneo\Pim\Structure\Component\Model\FamilyInterface $family
     *
     * @return void
     */
    public function addAttributeToAllVariants(AttributeInterface $attribute, FamilyInterface $family): void
    {
        /** @var array<\Akeneo\Pim\Structure\Component\Model\FamilyVariantInterface> $variants */
        $variants = $family->getFamilyVariants();

        $changed = false;

        if ($family->hasAttribute($attribute) === false) {
            $family->addAttribute($attribute);

            $changed = true;
        }

        foreach ($variants as $variant) {
            if ($variant->getNumberOfLevel() <= 0) {
                continue;
            }

            $set = $variant->getVariantAttributeSet($variant->getNumberOfLevel());

            if ($set === null || $set->hasAttribute($attribute)) {
                continue;
            }

            $set->addAttribute($attribute);

            $changed = true;
        }

        if ($changed) {
            $this->familySaver->save($family);
        }
    }
}
