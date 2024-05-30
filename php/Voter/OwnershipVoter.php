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

namespace Addiks\SymfonyGenerics\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Security;
use ErrorException;

final class OwnershipVoter implements VoterInterface
{
    public function __construct(
        private Security $security,
        private mixed $expectedAttribute = "ownership",
        private int $ifNotSupported = VoterInterface::ACCESS_ABSTAIN,
        private int $ifSubjectIsNotObject = VoterInterface::ACCESS_ABSTAIN,
        private int $ifCallerIsNull = VoterInterface::ACCESS_DENIED,
        private int $ifOwnerIsNull = VoterInterface::ACCESS_DENIED,
        private int $ifNotOwned = VoterInterface::ACCESS_DENIED,
        private bool $throwExceptionIfUnsupportedWhenExpected = true
    ) {
    }
    
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        if (!is_null($this->expectedAttribute) && !in_array($this->expectedAttribute, $attributes, true)) {
            return $this->ifNotSupported;
        }
        
        if (!is_object($subject)) {
            return $this->ifSubjectIsNotObject;
        }
        
        /** @var UserInterface|null $caller */
        $caller = $this->security->getUser();
        
        if (is_null($caller)) {
            return $this->ifCallerIsNull;
            
        } elseif ($subject instanceof UserInterface) {
            if ($subject === $caller) {
                return VoterInterface::ACCESS_GRANTED;
                
            } else {
                return $this->ifNotOwned;
            }
            
        } elseif ($subject instanceof Owned) {
            /** @var UserInterface|null $owner */
            $owner = $subject->owner();
            
            if (is_null($owner)) {
                return $this->ifOwnerIsNull;
                
            } elseif ($owner === $caller) {
                return VoterInterface::ACCESS_GRANTED;
                    
            } else {
                return $this->ifNotOwned;
            }
            
        } elseif ($subject instanceof OwnedFacade) {
            if ($subject->isOwnedBy($caller, $attributes)) {
                return VoterInterface::ACCESS_GRANTED;
                
            } else {
                return $this->ifNotOwned;
            }
            
        } elseif ($caller instanceof Owner) {
            if ($caller->owns($subject, $attributes)) {
                return VoterInterface::ACCESS_GRANTED;
                
            } else {
                return $this->ifNotOwned;
            }
            
        } elseif ($this->throwExceptionIfUnsupportedWhenExpected) {
            throw new ErrorException(sprintf(
                'Tried to vote by ownership when neither "%s" implements "%s" nor "%s" implements "%s" or "%s"!',
                get_class($caller),
                Owner::class,
                get_class($subject),
                Owned::class,
                OwnedFacade::class
            ));
            
        } else {
            return $this->ifNotSupported;
        }
    }
}
