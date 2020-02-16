<?php

namespace RoyBongers\CertbotDns01\Providers;

use Psr\Log\LoggerInterface;
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
        $login = $config->get('transip_login', $config->get('login'));
        $privateKey = $config->get('transip_private_key', $config->get('private_key'));

        Transip_ApiSettings::$login = trim($login);
        Transip_ApiSettings::$privateKey = trim($privateKey);

        $this->logger = $logger;
    }

    public function createChallengeDnsRecord(string $domain, string $challengeName, string $challengeValue): void
    {
        $dnsEntries = $this->getDnsEntries($domain);

        $challengeDnsEntry = new Transip_DnsEntry($challengeName, 60, Transip_DnsEntry::TYPE_TXT, $challengeValue);
        array_push($dnsEntries, $challengeDnsEntry);

        Transip_DnsService::setDnsEntries($domain, $dnsEntries);
    }

    public function cleanChallengeDnsRecord(string $domain, string $challengeName, string $challengeValue): void
    {
        $dnsEntries = $this->getDnsEntries($domain);

        foreach ($dnsEntries as $index => $dnsEntry) {
            if ($dnsEntry->name === $challengeName && $dnsEntry->content === $challengeValue) {
                $this->logger->debug(
                    sprintf('Removing challenge DNS record(%s 60 TXT %s)', $dnsEntry->name, $dnsEntry->content)
                );
                unset($dnsEntries[$index]);
            }
        }
        $dnsEntries = array_values($dnsEntries);
        Transip_DnsService::setDnsEntries($domain, $dnsEntries);
    }

    public function getDomainNames(): array
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