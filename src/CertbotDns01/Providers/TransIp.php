<?php

namespace RoyBongers\CertbotDns01\Providers;

use Psr\Log\LoggerInterface;
use RoyBongers\CertbotDns01\Certbot\ChallengeRecord;
use RoyBongers\CertbotDns01\Config;
use Transip_DnsEntry;
use Transip_DomainService;
use Transip_DnsService;
use Transip_ApiSettings;
use RoyBongers\CertbotDns01\Providers\Interfaces\ProviderInterface;

class TransIp implements ProviderInterface
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var array $domainNames */
    private $domainNames = [];

    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $login = $config->get('transip_login', $config->get('login'));
        $privateKey = $config->get('transip_private_key', $config->get('private_key'));

        Transip_ApiSettings::$login = trim($login);
        Transip_ApiSettings::$privateKey = trim($privateKey);
    }

    /**
     * Create a TXT DNS record via the provider's API.
     *
     * @param  ChallengeRecord  $challengeRecord
     */
    public function createChallengeDnsRecord(ChallengeRecord $challengeRecord): void
    {
        $dnsEntries = $this->getDnsEntries($challengeRecord->getDomain());

        $challengeDnsEntry = new Transip_DnsEntry(
            $challengeRecord->getRecordName(),
            60,
            Transip_DnsEntry::TYPE_TXT,
            $challengeRecord->getValidation()
        );

        array_push($dnsEntries, $challengeDnsEntry);
        Transip_DnsService::setDnsEntries($challengeRecord->getDomain(), $dnsEntries);
    }

    /**
     * Remove the created TXT record via the provider's API.
     *
     * @param  ChallengeRecord  $challengeRecord
     */
    public function cleanChallengeDnsRecord(ChallengeRecord $challengeRecord): void
    {
        $dnsEntries = $this->getDnsEntries($challengeRecord->getDomain());

        foreach ($dnsEntries as $index => $dnsEntry) {
            if ($dnsEntry->name === $challengeRecord->getRecordName() &&
                $dnsEntry->content === $challengeRecord->getValidation()
            ) {
                $this->logger->debug(
                    sprintf('Removing challenge DNS record(%s 60 TXT %s)', $dnsEntry->name, $dnsEntry->content)
                );
                unset($dnsEntries[$index]);
            }
        }
        $dnsEntries = array_values($dnsEntries);
        Transip_DnsService::setDnsEntries($challengeRecord->getDomain(), $dnsEntries);
    }

    /**
     * Return a simple array containing the domain names that can be managed via the API.
     *
     * @return iterable
     */
    public function getDomainNames(): iterable
    {
        if (empty($this->domainNames)) {
            $this->domainNames = Transip_DomainService::getDomainNames();
        }

        $this->logger->debug(sprintf('Domain names available: %s', implode(', ', $this->domainNames)));

        return $this->domainNames;
    }

    private function getDnsEntries(string $domainName): array
    {
        $dnsEntries = Transip_DomainService::getInfo($domainName)->dnsEntries;

        $this->logger->debug(sprintf('Existing DNS records for %s:', $domainName));

        foreach ($dnsEntries as $dnsEntry) {
            $this->logger->debug(
                sprintf(
                    '%s %s %s %s',
                    $dnsEntry->name,
                    $dnsEntry->expire,
                    $dnsEntry->type,
                    $dnsEntry->content
                )
            );
        }

        return $dnsEntries;
    }
}
