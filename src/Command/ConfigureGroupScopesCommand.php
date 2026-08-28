<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Group;
use App\Entity\GroupScope;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:configure-group-scopes',
    description: 'Create a group if needed and replace its scopes for an audience.'
)]
final class ConfigureGroupScopesCommand extends AbstractCommand
{
    public function __construct(
        private readonly GroupRepository $groupRepository,
        private readonly EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ) {
        parent::__construct($validator);
    }

    protected function configure(): void
    {
        $this
            ->addOption('group', null, InputOption::VALUE_REQUIRED, 'Group name to configure.')
            ->addOption('audience', null, InputOption::VALUE_REQUIRED, 'Protected-resource audience.')
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Scope to assign for the audience. This option must be provided at least once and may be repeated.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $requiredOptions = ['group', 'audience'];

        foreach ($requiredOptions as $option) {
            if (!$this->isValidStringOption($input, $option)) {
                $io->error(sprintf('The --%s option is required.', $option));

                return Command::INVALID;
            }
        }

        $groupName = $input->getOption('group');
        $scopes = array_values(array_unique($input->getOption('scope')));
        $audience = $input->getOption('audience');

        if ($scopes === []) {
            $io->error('At least one --scope option is required.');

            return Command::INVALID;
        }

        try {
            $created = $this->entityManager->wrapInTransaction(
                fn (): bool => $this->configureGroupScopes($io, $groupName, $audience, $scopes)
            );
        } catch (ValidationFailedException) {
            return Command::INVALID;
        }

        $io->success(sprintf(
            'Group "%s" was %s with %d scope(s) for audience "%s".',
            $groupName,
            $created ? 'created' : 'configured',
            count($scopes),
            $audience,
        ));

        return Command::SUCCESS;
    }

    private function configureGroupScopes(SymfonyStyle $io, string $groupName, string $audience, array $scopes): bool
    {
        $group = $this->groupRepository->findOneBy(['name' => $groupName]);
        $created = ($group === null);

        if ($group === null) {
            $group = new Group();
            $group->setName($groupName);

            $this->assertEntityIsValid($io, $group);
            $this->entityManager->persist($group);
        }

        $requestedScopes = array_fill_keys($scopes, true);
        foreach ($group->getScopes()->toArray() as $groupScope) {
            // skip in case of different audience
            if ($groupScope->getAudience() !== $audience) {
                continue;
            }

            // skip if scope already exists
            if (isset($requestedScopes[$groupScope->getScope()])) {
                unset($requestedScopes[$groupScope->getScope()]);
                continue;
            }

            $group->removeScope($groupScope);
            $this->entityManager->remove($groupScope);
        }

        foreach (array_keys($requestedScopes) as $scope) {
            $groupScope = new GroupScope();
            $groupScope->setAudience($audience);
            $groupScope->setScope($scope);
            $groupScope->setGroup($group);

            $this->assertEntityIsValid($io, $groupScope);
            $group->addScope($groupScope);
        }

        $this->entityManager->persist($group);

        return $created;
    }
}
