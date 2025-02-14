<?php

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }

    #[Route('/books', name: 'book_list')]
    public function listBooks(Request $request, BookRepository $bookRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        $selectedCategoryId = $request->query->get('category'); // Récupérer l'ID de la catégorie sélectionnée

        if ($selectedCategoryId) {
            $books = $bookRepository->findByCategory($selectedCategoryId);
        } else {
            $books = $bookRepository->findAll();
        }

        return $this->render('book/index.html.twig', [
            'books' => $books,
            'categories' => $categories,
            'selectedCategory' => $selectedCategoryId, // Envoyer la variable à Twig
        ]);
    }

}
