<?php

namespace App\Tests\Functional;

use App\Entity\Sale;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SeedDemoDataCommandTest extends KernelTestCase
{
    public function testSeedingCreatesADemoCompanyWithData(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $tester = new CommandTester($application->find('app:seed-demo'));
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('demo@afriflow.ai', $tester->getDisplay());

        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'demo@afriflow.ai']);
        self::assertNotNull($user);
        self::assertSame('Boutique Awa', $user->getCompany()->getName());

        $sales = $entityManager->getRepository(Sale::class)->findAll();
        self::assertNotEmpty($sales);
    }

    public function testSeedingTwiceFailsInsteadOfDuplicating(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $tester = new CommandTester($application->find('app:seed-demo'));
        $tester->execute([]);

        $secondRun = new CommandTester($application->find('app:seed-demo'));
        $exitCode = $secondRun->execute([]);

        self::assertSame(1, $exitCode);
    }
}
