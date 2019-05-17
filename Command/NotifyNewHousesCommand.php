<?php

namespace AppBundle\Command;

use AppBundle\Entity\HouseSearchParams;
use AppBundle\Service\SiteSection;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NotifyNewHousesCommand extends ContainerAwareCommand
{

    /**
     * @var \AppBundle\Entity\HouseRepository
     */
    private $houseRepo;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $manager;

    /**
     * @var boolean
     */
    private $quiet;

    /**
     * @var boolean
     */
    private $dryRun;

    /**
     * @var boolean
     */
    private $sendMail;

    /**
     * @var string
     */
    private $onlyUser;

    /**
     * @var \AppBundle\TransactionalEmail\MultilingualEmailNotificationServiceInterface
     */
    private $mailer;

    protected function configure()
    {
        $this->setName('app:user:notify-new-houses')
                ->setHelp('Searches for new houses by stored search parameters, and optionally sends an email notification')
                ->addOption('send-mail')
                ->addOption('dry-run', null, null, 'Does not update stored searches, does not send mail')
                ->addOption('only-user', null, InputOption::VALUE_REQUIRED, 'If set, only this user\'s search agent will be executed');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $searches = $this->getStoredSearches();

        if ($searches) {
            if (!$this->quiet) {
                $count = count($searches);
                $output->writeln("Found $count stored searches");
            }

            $this->executeAllSearches($searches, $output);
        } else if (!$this->quiet) {
            $output->writeln('Found no stored searches');
        }

        if (!$this->quiet) {
            $output->writeln('Done');
        }
    }

    /**
     * @return array
     */
    private function getStoredSearches()
    {
        return $this->manager
                        ->getRepository('AppBundle:HouseSearchParams')
                        ->findAll();
    }

    private function executeAllSearches($searches, OutputInterface $out)
    {
        $count = count($searches);
        $i = 0;

        /* @var $search HouseSearchParams */
        foreach ($searches as $search) {
            $i++;
            $user = $search->getMarketplaceUser() ?? $search->getPremiumUser();

            if ($this->onlyUser && $user->getEmail() != $this->onlyUser) {
                continue;
            }

            if (!$this->quiet) {
                $section = $search->getPublishedOnMarketplace() ? SiteSection::SECTION_MARKETPLACE : SiteSection::SECTION_PREMIUM;
                $out->writeln("Executing $i/$count: $section for {$user->getEmail()}");
            }

            $this->executeSearch($search, $out);
        }
    }

    private function executeSearch(HouseSearchParams $params, OutputInterface $out)
    {
        $houses = $this->houseRepo->search($params);
        $count = count($houses);

        if (!$this->quiet) {
            $out->writeln("Found $count houses");
        }

        if (!$this->dryRun) {
            $params->setOnlyNewerThan(new DateTime());
            $this->manager->persist($params);
            $this->manager->flush($params);
        }

        if ($count > 0 && $this->sendMail && !$this->dryRun) {

            if (($user = $params->getMarketplaceUser())) {
                $siteSection = SiteSection::SECTION_MARKETPLACE;
            } else if (($user = $params->getPremiumUser())) {
                $siteSection = SiteSection::SECTION_PREMIUM;
            }

            if ($params->getEnabled()) {
                $this->mailer->setLocale($user->getPreferredLocale());
                $this->mailer->sendNewHousesNotification($user, $siteSection, $houses);
            } else if (!$this->quiet) {
                $out->writeln('Search disabled, not sending mail');
            }
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->manager = $this->getContainer()->get('doctrine')->getManager();
        $this->houseRepo = $this->manager->getRepository('AppBundle:House');
        $this->quiet = $input->getOption('quiet') ? true : false;
        $this->dryRun = $input->getOption('dry-run') ? true : false;
        $this->onlyUser = $input->getOption('only-user');
        $this->sendMail = $input->getOption('send-mail') ? true : false;
        $this->mailer = $this->getContainer()->get('app.mailer');
    }

}
