<?php

namespace Drupal\wmsearch\Service\Api;

use Drush\Drush;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class TaskApi extends BaseApi
{
    /** @param string $id */
    public function getTask($id)
    {
        return $this->get(
            sprintf('_tasks/%s', $id)
        );
    }

    /** @param string $id */
    public function waitForCompletion($id)
    {
        $output = class_exists('Drush\Drush') && Drush::hasContainer()
            ? Drush::output()
            : null;

        do {
            $result = $this->getTask($id);
            $status = $result['task']['status'];
            sleep(1);

            if (!$output instanceof OutputInterface) {
                continue;
            }

            if (empty($progressBar)) {
                $progressBar = new ProgressBar($output, $result['task']['status']['total']);
                $progressBar->start();
            }

            $progressBar->setProgress($status['created'] + $status['updated'] + $status['deleted']);
        } while (empty($result['completed']));

        if ($output instanceof OutputInterface) {
            $output->writeln('');
        }
    }
}
