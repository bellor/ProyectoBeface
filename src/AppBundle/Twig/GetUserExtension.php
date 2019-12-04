<?php
namespace AppBundle\Twig {

    use Symfony\Bridge\Doctrine\RegistryInterface;

    class GetUserExtension extends \Twig_Extension
    {
        protected $doctrine;

        /**
         * GetUserExtension constructor.
         * @param RegistryInterface $doctrine
         */
        public function __construct(RegistryInterface $doctrine)
        {
            $this->doctrine = $doctrine;
        }

        /**
         * @return array
         */
        public function getFilters()
        {
            return array(
                new \Twig_SimpleFilter('get_user', array($this, 'getUserFilter'))
            );
        }

        /**
         * @return string
         */
        public function getName()
        {
            return 'get_user_extension';
        }


        /**
         * Devuelve Objeto User al pasar un id
         *
         * @param $user_id
         * @return User
         */
        public function getUserFilter($user_id)
        {
            $user_repo = $this->doctrine->getRepository('AppBundle:User');
            $user = $user_repo->findOneBy(array(
                'id' => $user_id
            ));

            if (!empty($user) && is_object($user)){
                $result = $user;
            } else {
                $result = false;
            }

            return $result;
        }

    }
}
