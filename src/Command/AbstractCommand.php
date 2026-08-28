<?php
declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractCommand extends Command
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function assertEntityIsValid(SymfonyStyle $io, object $entity, array $groups = ['create']): void
    {
        $violations = $this->validator->validate($entity, null, $groups);

        if (count($violations) === 0) {
            return;
        }

        foreach ($violations as $violation) {
            $io->error(sprintf(
                'Invalid %s: %s',
                $violation->getPropertyPath(),
                $violation->getMessage()
            ));
        }

        throw new ValidationFailedException($entity, $violations);
    }

    protected function isValidStringOption(InputInterface $input, string $name): bool
    {
        $value = $input->getOption($name);

        return is_string($value) && trim($value) !== '';
    }
}