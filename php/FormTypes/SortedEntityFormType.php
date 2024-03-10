<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\FormType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ObjectManager;

final class SortedEntityFormType extends EntityType
{

    public function configureOptions(OptionsResolver $resolver)
    {
        
        /** @var OptionsResolver $resolverProxy */
        $resolverProxy = new class() extends OptionsResolver
        {
            
        };
        
        parent::configureOptions($resolver);
        
        $resolver->addAllowedTypes('order-by', ['null', 'string']);
        
        $resolver->getD
        
    }
    
    public function getLoader(ObjectManager $manager, $queryBuilder, $class)
    {
        return parent::getLoader($manager, $queryBuilder, $class);
    }
    
    public function getBlockPrefix()
    {
        return 'sorted_entity';
    }

}
