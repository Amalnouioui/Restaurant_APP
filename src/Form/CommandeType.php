<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Commande;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateHeure', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date & Time',
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'PENDING',
                    'Confirmed' => 'CONFIRMED',
                    'In Progress' => 'IN_PROGRESS',
                    'Ready' => 'READY',
                    'Served' => 'SERVED',
                    'Cancelled' => 'CANCELLED',
                ],
                'label' => 'Status',
            ])
            ->add('total', NumberType::class, [
                'label' => 'Total',
                'scale' => 2,
            ])
            ->add('estimatedTime', IntegerType::class, [
                'label' => 'Estimated Time (minutes)',
                'required' => false,
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function(Client $client) {
                    return $client->getNom() . ' ' . $client->getPrenom() . ' (' . $client->getEmail() . ')';
                },
                'label' => 'Client',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
