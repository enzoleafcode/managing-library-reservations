<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;


#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/book/reserve/{id}', name: 'book_reserve')]
    public function reserveBook(int $id, BookRepository $bookRepository, EntityManagerInterface $entityManager, Security $security): RedirectResponse
    {
        $book = $bookRepository->find($id);
        $user = $security->getUser();

        if (!$book || !$user || !$book->isAvailability()) {
            $this->addFlash('danger', 'Ce livre n\'est plus disponible.');
            return $this->redirectToRoute('book_list');
        }

        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->addBook($book);
        $reservation->setReservationDate(new \DateTime());
        $reservation->setStatus('EN ATTENTE');

        // Désactiver la disponibilité du livre si nécessaire
        $book->setAvailability(false);

        $entityManager->persist($reservation);
        $entityManager->persist($book);
        $entityManager->flush();

        $this->addFlash('success', 'Livre réservé avec succès.');
        return $this->redirectToRoute('book_list');
    }

    #[Route('/reservation/my-reservations', name: 'my_reservations')]
    public function myReservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur connecté

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour voir vos réservations.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer uniquement les réservations de l'utilisateur connecté
        $reservations = $reservationRepository->findBy(['user' => $user]);

        return $this->render('reservation/my_reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservation/{id}/remove-book/{bookId}', name: 'user_remove_book', methods: ['POST'])]
    public function removeBookFromReservation(int $id, int $bookId, ReservationRepository $reservationRepository, BookRepository $bookRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $reservationRepository->find($id);
        $book = $bookRepository->find($bookId);

        if (!$reservation || !$book || $reservation->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier cette réservation.');
            return $this->redirectToRoute('my_reservations');
        }

        // Vérifier si le livre est bien dans la réservation
        if (!$reservation->getBooks()->contains($book)) {
            $this->addFlash('danger', 'Ce livre ne fait pas partie de votre réservation.');
            return $this->redirectToRoute('my_reservations');
        }

        // Supprimer le livre de la réservation
        $reservation->removeBook($book);
        $book->setAvailability(true); // Rendre le livre disponible à nouveau
        $entityManager->persist($book);

        // Si la réservation ne contient plus de livres, on la supprime
        if ($reservation->getBooks()->isEmpty()) {
            $entityManager->remove($reservation);
            $this->addFlash('info', 'Votre réservation a été supprimée car il ne restait plus de livres.');
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le livre a été retiré de votre réservation.');
        return $this->redirectToRoute('my_reservations');
    }
    
    #[Route('/book/cancel/{id}', name: 'book_cancel_reservation', methods: ['POST'])]
    public function cancelReservation(int $id, BookRepository $bookRepository, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager, Security $security): RedirectResponse
    {
        $book = $bookRepository->find($id);
        $user = $security->getUser();

        if (!$book || !$user) {
            $this->addFlash('danger', 'Une erreur est survenue.');
            return $this->redirectToRoute('book_list');
        }

        // Trouver la réservation de l'utilisateur avec ce livre
        $reservations = $reservationRepository->findBy(['user' => $user]);
        foreach ($reservations as $reservation) {
            if ($reservation->getBooks()->contains($book)) {
                $reservation->removeBook($book);
                $book->setAvailability(true); // Remettre le livre disponible

                // Si la réservation n'a plus de livres, la supprimer
                if ($reservation->getBooks()->isEmpty()) {
                    $entityManager->remove($reservation);
                }

                $entityManager->persist($book);
                $entityManager->flush();

                $this->addFlash('success', 'Réservation annulée.');
                return $this->redirectToRoute('book_list');
            }
        }

        $this->addFlash('danger', 'Ce livre n\'est pas dans vos réservations.');
        return $this->redirectToRoute('book_list');
    }

}
