<?php

namespace App\Form;

use App\Entity\Website;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ShortUrlFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'Podaj link do skrócenia'
            ])
            ->add('include', CheckboxType::class, [
                'label' => 'Uwzględniaj w rankingu',
                'required' => false,
                'attr' => ['style' => 'display:none;', 'checked' => 'checked'],
            ]
            )
            #->add('shorturl')
            #->add('visited')
            #->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Website::class,
        ]);
    }
}
