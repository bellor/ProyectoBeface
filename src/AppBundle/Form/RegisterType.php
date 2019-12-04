<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class RegisterType extends AbstractType
{

    /**
     * Define los campos del formulario de registro
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('name', TextType::class, array(
            'label' => 'Nombre',
            'required' => false,
            'attr' => array(
                'class' => 'form-name form-control',
                'placeholder' => 'Nombre'
            )
        ))
        ->add('surname', TextType::class, array(
            'label' => 'Apellido',
            'required' => 'required',
            'attr' => array(
                'class' => 'form-surname form-control',
                'placeholder' => 'Apellido'
            )
        ))
        ->add('nick', TextType::class, array(
            'label' => 'Nick',
            'required' => 'required',
            'attr' => array(
                'class' => 'form-nick form-control nick-input',
                'placeholder' => 'Nick'
            )
        ))
        ->add('email', EmailType::class, array(
            'label' => 'Correo electrónico',
            'required' => 'required',
            'attr' => array(
                'class' => 'form-email form-control',
                'placeholder' => 'Correo electrónico'
            )
        ))
        ->add('password', PasswordType::class, array(
            'label' => 'Contraseña',
            'required' => 'required',
            'attr' => array(
                'class' => 'form-password form-control',
                'placeholder' => 'Contraseña'
            )
        ))
        ->add('Registrarse', SubmitType::class, array(
            "attr" => array(
                "class" => "btn btn-primary button-r",
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
            'data_class' => 'AppBundle\Entity\User'
        ));
    }


    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }


}
