<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Console\Command;

use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
use Symplify\EasyCodingStandard\FixerRunner\Finder\FixerFinder;
use Symplify\EasyCodingStandard\SniffRunner\Sniff\Finder\SniffFinder;
use Symplify\PackageBuilder\Composer\VendorDirProvider;
use Symplify\PackageBuilder\Console\Command\CommandNaming;

final class ShowAllCheckersCommand extends Command
{
    /**
     * @var EasyCodingStandardStyle
     */
    private $easyCodingStandardStyle;

    /**
     * @var SniffFinder
     */
    private $sniffFinder;

    /**
     * @var FixerFinder
     */
    private $fixerFinder;

    public function __construct(EasyCodingStandardStyle $easyCodingStandardStyle, SniffFinder $sniffFinder, FixerFinder $fixerFinder)
    {
        parent::__construct();

        $this->easyCodingStandardStyle = $easyCodingStandardStyle;
        $this->sniffFinder = $sniffFinder;
        $this->fixerFinder = $fixerFinder;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Show all available checkers');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Filter by name to get only specific checkers, e.g. "array"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @todo include /src and /packages directories as well, allow many directories
        $checkers = $this->sniffFinder->findAllSniffClassesInDirectory(VendorDirProvider::provide())
            + $this->fixerFinder->findAllFixerClassesInDirectory(VendorDirProvider::provide());

        if ($input->getOption('name')) {
            $checkers = $this->filterCheckersByName($checkers, $input->getOption('name'));
        }

        if (! count($checkers)) {
            $this->easyCodingStandardStyle->warning('No checkers found');
            return 0;
        }

        sort($checkers);
        $this->easyCodingStandardStyle->listing($checkers);

        $this->easyCodingStandardStyle->success(sprintf(
            'Loaded %d checker%s in total',
            count($checkers),
            count($checkers) === 1 ? '' : 's'
        ));

        return 0;
    }

    /**
     * @param string[] $checkers
     * @return string[]
     */
    private function filterCheckersByName(array $checkers, string $name): array
    {
        $filteredCheckers = [];
        foreach ($checkers as $checker) {
            if (Strings::match($checker, sprintf('#%s#i', preg_quote($name)))) {
                $filteredCheckers[] = $checker;
            }
        }

        return $filteredCheckers;
    }
}
