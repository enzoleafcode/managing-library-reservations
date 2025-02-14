<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Form\UserType;
use App\Repository\ReservationRepository;
use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/books', name: 'admin_books')]
    public function adminBooks(BookRepository $bookRepository, CategoryRepository $categoryRepository): Response
    {
        $books = $bookRepository->findAll();
        $categories = $categoryRepository->findAll(); // Récupérer toutes les catégories

        return $this->render('admin/books.html.twig', [
            'books' => $books,
            'categories' => $categories, // Envoyer les catégories à Twig
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function manageUsers(UserRepository $userRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/reservations', name: 'admin_reservations')]
    public function manageReservations(ReservationRepository $reservationRepository): Response
    {
        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/reservation/{id}/cancel', name: 'admin_cancel_reservation', methods: ['POST'])]
    public function cancelReservation(int $id, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            $this->addFlash('danger', 'Réservation non trouvée.');
            return $this->redirectToRoute('admin_reservations');
        }

        // Remettre les livres en stock
        foreach ($reservation->getBooks() as $book) {
            $book->setAvailability(true);
            $entityManager->persist($book);
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Réservation annulée avec succès.');
        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/admin/reservation/{id}/confirm', name: 'admin_confirm_reservation', methods: ['POST'])]
    public function confirmReservation(int $id, ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            $this->addFlash('danger', 'Réservation non trouvée.');
            return $this->redirectToRoute('admin_reservations');
        }

        // 🔥 Mettre à jour l'état de confirmation
        $reservation->setConfirmed(true);
        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Réservation confirmée.');
        return $this->redirectToRoute('admin_reservations');
    }


    #[Route('/admin/user/{id}/edit', name: 'admin_edit_user')]
    public function editUser(int $id, UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('danger', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('admin_users');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/edit_user.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/user/{id}/delete', name: 'admin_delete_user', methods: ['POST'])]
    public function deleteUser(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('danger', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('admin_users');
        }

        // 🔥 Vérifier si l'utilisateur est un admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer un administrateur.');
            return $this->redirectToRoute('admin_users');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/book/new', name: 'admin_add_book')]
    public function addBook(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $book = new Book();
        $categories = $categoryRepository->findAll();

        $form = $this->createForm(BookType::class, $book, [
            'categories' => $categories
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($book);
            $entityManager->flush();

            $this->addFlash('success', 'Livre ajouté avec succès.');
            return $this->redirectToRoute('admin_books');
        }

        return $this->render('admin/add_book.html.twig', [
            'form' => $form->createView(),
            'categories' => $categories
        ]);
    }

    #[Route('/admin/book/{id}/edit', name: 'admin_edit_book')]
    public function editBook(int $id, BookRepository $bookRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $book = $bookRepository->find($id);

        if (!$book) {
            $this->addFlash('danger', 'Livre non trouvé.');
            return $this->redirectToRoute('admin_books');
        }

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($book);
            $entityManager->flush();

            $this->addFlash('success', 'Livre modifié avec succès.');
            return $this->redirectToRoute('admin_books');
        }

        return $this->render('admin/edit_book.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/book/{id}/delete', name: 'admin_delete_book', methods: ['POST'])]
    public function deleteBook(int $id, BookRepository $bookRepository, EntityManagerInterface $entityManager): Response
    {
        $book = $bookRepository->find($id);

        if (!$book) {
            $this->addFlash('danger', 'Livre non trouvé.');
            return $this->redirectToRoute('admin_books');
        }

        // Vérifier si le livre est associé à une réservation
        if (!$book->getReservations()->isEmpty()) {
            $this->addFlash('danger', 'Impossible de supprimer ce livre car il est associé à une réservation.');
            return $this->redirectToRoute('admin_books');
        }

        $entityManager->remove($book);
        $entityManager->flush();

        $this->addFlash('success', 'Livre supprimé avec succès.');
        return $this->redirectToRoute('admin_books');
    }

}
