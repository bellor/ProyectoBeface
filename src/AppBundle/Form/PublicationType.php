<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;


class PublicationType extends AbstractType
{

    /**
     * Define los campos del formulario de publicacion
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('texto', TextareaType::class, array(
            'label' => 'Mensaje',
            'required' => 'required',
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('image', FileType::class, array(
            'label' => 'Foto',
            'required' => false,
            'data_class' => null,
            'attr' => array(
                'class' => 'form-control'
            )
        ))
        ->add('Crear', SubmitType::class, array(
            "attr" => array(
                "class" => "btn btn-primary"
            )
        ))
        ;
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Publication'
        ));
    }


    public function getBlockPrefix()
    {
        return 'appbundle_publication';
    }


}
