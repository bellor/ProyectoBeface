<?php
namespace AppBundle\Twig {

// Activo 'RegistryInterface' para poder usar Doctrine
    use Symfony\Bridge\Doctrine\RegistryInterface;

    class FollowExtension extends \Twig_Extension
    {
        /* CARGAMOS DOCTRINE **************************************************************/
  // Para usar 'Doctrine' necesiatmos de 'RegistryInterface'
        protected $doctrine;

        /**
         * FollowExtension constructor.
         * @param RegistryInterface $doctrine
         */
        public function __construct(RegistryInterface $doctrine)
        {
            $this->doctrine = $doctrine;
        }

        
        /* DEFINIMOS NOMBRE DEL FILTRO + FUNCIÓN FILTRO ***********************************/
        /**
         * @return array
         */
        public function getFilters()
        {
        /*
       * indicamos como llamaremos al filtro `isfollow'
       * y que función ejecutará el filtro `isfollowFilter`
       */
        return array(
            new \Twig_SimpleFilter('isfollow', array($this, 'isfollowFilter'))
        );
    }
    
    /* DEFINIMOS LA FUNCIÓN ***********************************************************/
        /**
         * @return string
         */
        public function getName()
        {
            return 'follow_extension';
        }

        /* FUNCIÓN FILTRO *****************************************************************/
        /**
         * Método comprueba si se sigue o no a un usuario
         *
         * @param $user
         * @param $followed_id
         * @return bool
         */
        public function isfollowFilter($user, $followed_id)
        {
            $follow_repo = $this->doctrine->getRepository('AppBundle:Follow');
            $followed = $follow_repo->findOneBy(array(
                'user' => $user,
                'followed' => $followed_id
            ));

            if (!empty($followed) && is_object($followed)){
                $result = true;
            } else {
                $result = false;
            }

            return $result;
        }

    }
}
