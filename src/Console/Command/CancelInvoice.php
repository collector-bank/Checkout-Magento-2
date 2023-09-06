<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webbhuset\CollectorCheckout\Test\InitiateCheckout;

class CancelInvoice extends Command
{
    /**
     * @var State
     */
    private $appState;
    /**
     * @var \Webbhuset\CollectorCheckout\Test\CancelInvoice
     */
    private $cancelInvoice;

    public function __construct(
        State $appState,
        \Webbhuset\CollectorCheckout\Test\CancelInvoice $cancelInvoice,
        string $name = null
    ) {
        parent::__construct($name);

        $this->appState = $appState;
        $this->cancelInvoice = $cancelInvoice;
    }

    protected function configure()
    {
        $options = [
            new InputOption(
                'orderId',
                null,
                InputOption::VALUE_REQUIRED,
                'Walley order id'
            ),
        ];
        $this->setName('walley:test:cancelinvoice')
            ->setDescription('Cancel invoice')
            ->setDefinition($options);

        parent::configure();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->appState->setAreaCode('adminhtml');
        $orderId = (string)$input->getOption('orderId');
        $result = json_encode($this->cancelInvoice->execute($orderId));
        $output->writeln($result);

        return 1;
    }
}
