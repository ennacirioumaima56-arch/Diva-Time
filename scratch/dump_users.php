<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\User;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$userRepository = $entityManager->getRepository(User::class);

$users = $userRepository->findAll();

echo "EMAIL | PASSWORD HASH | IS HASHED?\n";
echo "--------------------------------------------------\n";
foreach ($users as $user) {
    $password = $user->getPassword();
    $isHashed = (strpos($password, '$2y$') === 0 || strpos($password, '$argon2id$') === 0) ? "YES" : "NO (PLAIN TEXT?)";
    echo $user->getEmail() . " | " . substr($password, 0, 20) . "... | " . $isHashed . "\n";
}
