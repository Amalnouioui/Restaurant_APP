<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/bootstrap.php';

$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$platRepository = $entityManager->getRepository('App\Entity\Plat');

// Check if plats already exist
$existingPlats = $platRepository->findAll();

if (count($existingPlats) == 0) {
    $dishes = [
        ['nomPlat' => 'Margherita Pizza', 'description' => 'Classic pizza with tomato, mozzarella, and basil', 'prix' => 12.99],
        ['nomPlat' => 'Caesar Salad', 'description' => 'Fresh romaine lettuce with caesar dressing and croutons', 'prix' => 9.99],
        ['nomPlat' => 'Grilled Salmon', 'description' => 'Fresh salmon fillet with lemon butter sauce', 'prix' => 18.99],
        ['nomPlat' => 'Spaghetti Carbonara', 'description' => 'Traditional Italian pasta with eggs, bacon, and parmesan', 'prix' => 14.99],
        ['nomPlat' => 'Burger', 'description' => 'Juicy beef burger with cheese, lettuce, and tomato', 'prix' => 11.99],
        ['nomPlat' => 'Chicken Breast', 'description' => 'Grilled chicken breast with herbs and garlic', 'prix' => 15.99],
    ];
    
    foreach ($dishes as $dish) {
        $plat = new \App\Entity\Plat();
        $plat->setNomPlat($dish['nomPlat']);
        $plat->setDescription($dish['description']);
        $plat->setPrix($dish['prix']);
        
        $entityManager->persist($plat);
    }
    
    $entityManager->flush();
    echo "✅ Test dishes inserted successfully!\n";
} else {
    echo "✅ Dishes already exist in database (" . count($existingPlats) . " found)\n";
}
