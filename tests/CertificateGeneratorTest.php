<?php

namespace ElevenLabs\DockerHostManager;

use ElevenLabs\DockerHostManager\Cert\Subject;
use ElevenLabs\DockerHostManager\Crypto\RsaKeyGenerator;
use ElevenLabs\DockerHostManager\File\FileFactory;
use ElevenLabs\DockerHostManager\File\LocalDirectory;
use ElevenLabs\DockerHostManager\File\LocalFile;
use PHPUnit\Framework\TestCase;
use X509\GeneralName\GeneralName;

class CertificateGeneratorTest extends TestCase
{
    private $issuerCertificateBundle;
    private $subject;
    private $rsaKeyGenerator;

    public function setUp(): void
    {
        $this->rsaKeyGenerator = new RsaKeyGenerator();

        $this->subject = new Subject(
            $organizationName  = 'ACME Inc.',
            $commonName        = 'ACME Root CA',
            $countryName       = 'FR',
            $stateProvinceName = 'Paris',
            $localityName      = 'Paris'
        );

        $this->issuerCertificateBundle = (
            new RootCertificate(
                new LocalDirectory(__DIR__ . '/Fixtures/root-ca'),
                $this->rsaKeyGenerator,
                $this->subject
            )
        )->get();
    }

    /** @test */
    public function it_can_generate_a_valid_signed_certificate()
    {
        $certificateGenerator = new CertificateGenerator(
            $this->subject,
            $this->rsaKeyGenerator,
            $this->issuerCertificateBundle
        );

        $signedCertificateBundle = $certificateGenerator->generate(['foo.domain.fr']);

        assertTrue(
            $signedCertificateBundle->getCertificate()->verify(
                $this->issuerCertificateBundle->getPrivateKeyInfo()->publicKeyInfo()
            )
        );
    }

    /** @test */
    public function it_use_the_first_given_dns_name_as_a_common_name()
    {
        $certificateGenerator = new CertificateGenerator(
            $this->subject,
            $this->rsaKeyGenerator,
            $this->issuerCertificateBundle
        );

        $signedCertificateBundle = $certificateGenerator->generate(['foo.domain.fr', 'bar.domain.fr']);

        $attributeValue = $signedCertificateBundle
            ->getCertificate()
            ->tbsCertificate()
            ->subject()
            ->firstValueOf('commonName');

        assertThat($attributeValue->stringValue(), equalTo('foo.domain.fr'));
    }

    /** @test */
    public function it_dns_names_as_subject_alternative_names()
    {
        $certificateGenerator = new CertificateGenerator(
            $this->subject,
            $this->rsaKeyGenerator,
            $this->issuerCertificateBundle
        );

        $signedCertificateBundle = $certificateGenerator->generate(['foo.domain.fr', 'bar.domain.fr']);

        $subjectAlternativeNames = $signedCertificateBundle
            ->getCertificate()
            ->tbsCertificate()
            ->extensions()
            ->subjectAlternativeName()
            ->names();

        assertThat($subjectAlternativeNames->count(), equalTo(2));

        assertThat(
            $subjectAlternativeNames->allOf(GeneralName::TAG_DNS_NAME),
            equalTo(['foo.domain.fr', 'bar.domain.fr'])
        );
    }
}