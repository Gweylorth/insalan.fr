<?php

namespace InsaLan\TournamentBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use InsaLan\TournamentBundle\Entity\Participant;

class TournamentRepository extends EntityRepository
{
    public function findOpened() {
        $query = $this->createQueryBuilder('t')
            ->where('t.registrationOpen <= :now AND t.registrationClose >= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery();
        return $query->getResult();
    }

    public function getFreeSlots($tournamentId) {
        $tournament = $this->findOneById($tournamentId);
        $freeSlots = $tournament->getRegistrationLimit();
        foreach($tournament->getParticipants() as $participant) {
            if ($participant->getValidated() === Participant::STATUS_VALIDATED) {
                $freeSlots--;
            }
        }
        return $freeSlots;
    }
    
    public function selectWaitingParticipant($tournamentId) {
        $tournament = $this->findOneById($tournamentId);
        $participants = array();
        foreach($tournament->getParticipants() as $participant) {
            if ($participant->getValidated() === Participant::STATUS_WAITING) {
                $participants[] = $participant;
            }
        }
        return $participants;
    }

    /**
     * Get an associative array of unavailable placements for fast determination.
     * Only keys where placement is unavailable are set, because we cannot determine
     * the number of places.
     * 
     * @param  Tournament $t
     * @return Associative array (integer=>true)
     */
    public function getUnavailablePlacements(Tournament $t) {
        $em = $this->getEntityManager();
        $res = $em->createQuery("
            SELECT DISTINCT p.placement FROM InsaLanTournamentBundle:Participant p
            WHERE p.placement IS NOT NULL AND p.tournament = :t")
                ->setParameter("t", $t)
                ->getResult();

        $out = array();
        foreach($res as $p) {
            $out[$p["placement"]] = true;
        }
        return $out;
    }
}
