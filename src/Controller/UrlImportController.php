<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Url;
use App\Form\UrlImportType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use League\Uri\Http;

class UrlImportController extends AbstractController
{
    
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/url/import', name: 'url_import', methods: ['GET', 'POST'])]
    public function import(Request $request)
    {
        $form = $this->createForm(UrlImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $urls = $this->parseCsvFile($file);

            $batchSize = 100; // Number of URLs to process in each batch
            $entityManager = $this->entityManager;
            $urlRepository = $entityManager->getRepository(Url::class);

            $totalUrls = count($urls);
            $addedUrls = 0;

            for ($i = 0; $i < $totalUrls; $i++) {
                $url = $urls[$i];

                // Check if URL already exists in the database
                if ($urlRepository->findBy(['url' => $url])) {
                    continue;
                }

                // Create a new Url entity and set the URL value
                $urlEntity = new Url();
                $urlEntity->setUrl($url);

                // Persist the entity
                $entityManager->persist($urlEntity);

                // Process URLs in batches to improve performance
                if (($i + 1) % $batchSize === 0) {
                    $entityManager->flush();
                    $entityManager->clear();
                }

                $addedUrls++;
            }

            // Flush remaining entities
            $entityManager->flush();
            $entityManager->clear();

            $this->addFlash('success', sprintf('Successfully imported %d URLs.', $addedUrls));

            return $this->redirectToRoute('url_import');
        }

        return $this->render('url_import/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // src/Controller/UrlImportController.php

// ...

    private function parseCsvFile($file)
    {
        $urls = [];

        // Check if a file was uploaded
        if ($file instanceof UploadedFile) {
            $csvFile = fopen($file->getPathname(), 'r');

            // Iterate through each line of the CSV file
            while (($data = fgetcsv($csvFile)) !== false) {
                // Assuming the URL is in the first column (index 0) of each row
                $url = $data[0];

                // Validate and sanitize the URL, you can add your own validation logic here
                if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
                    $urls[] = $url;
                }
            }

            fclose($csvFile);
        }

        return $urls;
    }

    private function normalizeUrl(string $url): string
    {
        $uri = Http::createFromString($url);

        $uri = $uri->withScheme($uri->getScheme() ? strtolower($uri->getScheme()) : '')
                ->withHost($uri->getHost() ? strtolower($uri->getHost()) : '')
                ->withPort($uri->getPort() === 80 ? null : $uri->getPort())
                ->withQuerySorted();

        return (string) $uri;
    }


}
