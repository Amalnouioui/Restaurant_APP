<?php

namespace App\Tests\Controller;

use App\Entity\LigneCommande;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LigneCommandeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $ligneCommandeRepository;
    private string $path = '/ligne/commande/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->ligneCommandeRepository = $this->manager->getRepository(LigneCommande::class);

        foreach ($this->ligneCommandeRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('LigneCommande index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'ligne_commande[quantite]' => 'Testing',
            'ligne_commande[commande]' => 'Testing',
            'ligne_commande[plat]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->ligneCommandeRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new LigneCommande();
        $fixture->setQuantite('My Title');
        $fixture->setCommande('My Title');
        $fixture->setPlat('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('LigneCommande');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new LigneCommande();
        $fixture->setQuantite('Value');
        $fixture->setCommande('Value');
        $fixture->setPlat('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'ligne_commande[quantite]' => 'Something New',
            'ligne_commande[commande]' => 'Something New',
            'ligne_commande[plat]' => 'Something New',
        ]);

        self::assertResponseRedirects('/ligne/commande/');

        $fixture = $this->ligneCommandeRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getQuantite());
        self::assertSame('Something New', $fixture[0]->getCommande());
        self::assertSame('Something New', $fixture[0]->getPlat());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new LigneCommande();
        $fixture->setQuantite('Value');
        $fixture->setCommande('Value');
        $fixture->setPlat('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/ligne/commande/');
        self::assertSame(0, $this->ligneCommandeRepository->count([]));
    }
}
