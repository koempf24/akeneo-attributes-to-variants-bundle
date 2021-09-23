<?php

declare(strict_types = 1);

namespace Koempf\AttributeToVariantsBundle\Command;

use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Pim\Structure\Component\Repository\FamilyRepositoryInterface;
use Akeneo\UserManagement\Bundle\Manager\UserManager;
use Koempf\AttributeToVariantsBundle\Adder\AttributeToVariantsAdder;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * php bin/console koempf:attribute-to-variants:add --attribute=attribute_code --families=family_code1,family_code2,family_code3
 */
class AddAttributeToVariantsCommand extends Command
{
    /**
     * @var string
     */
    private const ATTRIBUTE_OPTION = 'attribute';

    /**
     * @var string
     */
    private const FAMILIES_OPTION = 'families';

    /**
     * @var string
     */
    private const USER_NAME_OPTION = 'user-name';

    /**
     * @var string
     */
    protected static $defaultName = 'koempf:attribute-to-variants:add';

    private AttributeRepositoryInterface $attributeRepository;

    private FamilyRepositoryInterface $familyRepository;

    private AttributeToVariantsAdder $attributeToVariantsAdder;

    private UserManager $userManager;

    private TokenStorageInterface $tokenStorage;

    /**
     * @param \Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface $attributeRepository
     * @param \Akeneo\Pim\Structure\Component\Repository\FamilyRepositoryInterface $familyRepository
     * @param \Koempf\AttributeToVariantsBundle\Adder\AttributeToVariantsAdder $attributeToVariantsAdder
     * @param \Akeneo\UserManagement\Bundle\Manager\UserManager $userManager
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage $tokenStorage
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        FamilyRepositoryInterface $familyRepository,
        AttributeToVariantsAdder $attributeToVariantsAdder,
        UserManager $userManager,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct();
        $this->attributeRepository = $attributeRepository;
        $this->familyRepository = $familyRepository;
        $this->attributeToVariantsAdder = $attributeToVariantsAdder;
        $this->userManager = $userManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(static::ATTRIBUTE_OPTION, 'attr', InputOption::VALUE_REQUIRED)
            ->addOption(static::FAMILIES_OPTION, 'fam', InputOption::VALUE_REQUIRED)
            ->addOption(static::USER_NAME_OPTION, 'user', InputOption::VALUE_OPTIONAL, '', 'admin');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createUserToken($input->getOption(static::USER_NAME_OPTION));

        $attribute = $this->getAttribute($input);
        $families = $this->getFamilies($input);

        foreach ($families as $family) {
            $output->writeln('Add ' . $attribute->getCode() . ' to ' . $family->getCode() . '..');
            $this->attributeToVariantsAdder->addAttributeToAllVariants($attribute, $family);
        }

        return 0;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @throws \RuntimeException
     *
     * @return \Akeneo\Pim\Structure\Component\Model\AttributeInterface
     */
    private function getAttribute(InputInterface $input): AttributeInterface
    {
        $attributeCode = $input->getOption(static::ATTRIBUTE_OPTION);

        $attribute = $this->attributeRepository->findOneByIdentifier($attributeCode);

        if ($attribute === null) {
            throw new RuntimeException('Attribute "' . $attributeCode . '" not found!');
        }

        return $attribute;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @throws \RuntimeException
     *
     * @return \Akeneo\Pim\Structure\Component\Model\FamilyInterface[]
     */
    private function getFamilies(InputInterface $input): array
    {
        $familyCodes = preg_split('/\s*,\s*/', $input->getOption(static::FAMILIES_OPTION), -1, PREG_SPLIT_NO_EMPTY);

        if ($familyCodes === ['*']) {
            /** @var \Akeneo\Pim\Structure\Component\Model\FamilyInterface[] $families */
            $families = $this->familyRepository->findAll();
        } else {
            /** @var \Akeneo\Pim\Structure\Component\Model\FamilyInterface[] $families */
            $families = $this->familyRepository->findBy(['code' => $familyCodes]);
        }

        if (empty($families)) {
            throw new RuntimeException('No families found with "' . implode(', ', $familyCodes) . '"!');
        }

        return $families;
    }

    /**
     * @param string $userName
     *
     * @return void
     */
    private function createUserToken(string $userName): void
    {
        $user = $this->userManager->findUserByUsername($userName);

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());

        $this->tokenStorage->setToken($token);
    }
}
