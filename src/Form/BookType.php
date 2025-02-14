<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('author', TextType::class, ['label' => 'Auteur'])
            ->add('type', TextType::class, [ // ✅ Ajout du champ "Type"
                'label' => 'Type',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name', // ✅ Utilise le nom de la catégorie
                'expanded' => true, // ✅ Affichage en cases à cocher
                'multiple' => true, // ✅ Permet de sélectionner plusieurs catégories
                'attr' => ['class' => 'form-check'],
            ])
            ->add('availability', CheckboxType::class, [
                'label'    => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
