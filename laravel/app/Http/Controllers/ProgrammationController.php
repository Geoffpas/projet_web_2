<?php

namespace App\Http\Controllers;

use App\Models\Artiste;
use App\Models\Programmation;
use App\Models\Spectacle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProgrammationController extends Controller

{
    /**
     * Affichage de la page programmation
     *
     * @return View
     */
    public function index()
    {
        $programmations = Programmation::all();

        $prestations = $this->trier($programmations);


        $programmations = $programmations->zip($prestations);

        return view('programmation.index', [
            "programmation" => $programmations,
        ]);
    }

    private function trier($programmations){
        return $programmations->map(function($prog) {
            return $prog->prestations()->sortBy([
                fn($a, $b) => $a->heure > $b->heure
            ]);
        });
    }

    /**
     * formulaire de modification d'une programmation(ajout spectacles et artistes)
     *
     * @param int $id
     * @return View
     */
    public function edit($id)
    {
        $programmation = Programmation::find($id);

        $artistes = $programmation->artistes;
        $spectacles = $programmation->spectacles;

        return view('programmation.edit', [
            'programmation' => $programmation,
            'artistes' => $artistes,
            'spectacles' => $spectacles,

        ]);
    }

    /**
     * Traitement de la modification
     *
     * @param Request $request
     * @param int $id
     * @return Redirect/Response
     */
    public function update(Request $request, $id)
    {

        $programmation = Programmation::findOrFail($id);

        $validationRules = [
            'nom_scene' => 'nullable',
            'heure_show' => 'nullable',
            'image' => 'nullable|image',
            'nom_spectacle' => 'nullable',
            'heure_spectacle' => 'nullable',
            'image_spectacle' => 'nullable|image',
        ];


        $valides = $request->validate($validationRules, [

            'image.image' => 'Le fichier doit être une image valide.',
            'image_spectacle.image' => 'Le fichier doit être une image valide.',
        ]);

        if ($request->filled('nom_scene') && $request->filled('heure_show')) {
            $artiste = new Artiste;
            $artiste->nom_scene = $valides['nom_scene'];
            $artiste->heure_show = $valides['heure_show'];


            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                Storage::putFile("public/uploads", $request->image);
                $artiste->image = "/storage/uploads/" . $request->image->hashName();
            } else {
                $artiste->image = "/images/default.png";
            }

            $artiste->save();

            $programmation->artistes()->attach($artiste->id);
        }

        if ($request->filled('nom_spectacle') && $request->filled('heure_spectacle')) {

            $spectacle = new Spectacle;
            $spectacle->nom = $valides['nom_spectacle'];
            $spectacle->heure = $valides['heure_spectacle'];


            if ($request->hasFile('image_spectacle') && $request->file('image_spectacle')->isValid()) {

                Storage::putFile("public/uploads", $request->file('image_spectacle'));
                $spectacle->image = "/storage/uploads/" . $request->file('image_spectacle')->hashName();
            } else {
                $spectacle->image = "/logos/centre_gold_blanc.png";
            }

            $spectacle->save();

            $programmation->spectacles()->attach($spectacle->id);
        }

        $artistesFilled = $request->filled('nom_scene') || $request->filled('heure_show');
        $spectaclesFilled = $request->filled('nom_spectacle') || $request->filled('heure_spectacle');

        if ((!$artistesFilled && !$spectaclesFilled) ||
            (!$request->filled('nom_scene') && !$request->filled('heure_show') && !$request->filled('nom_spectacle') && !$request->filled('heure_spectacle'))
        ) {

            return redirect()
                ->back()
                ->with('error', 'Le formulaire est vide. Veuillez remplir au moins une partie (artistes ou spectacles).');
        }

        if ((!$request->filled('nom_scene') || !$request->filled('heure_show')) &&
            (!$request->filled('nom_spectacle') || !$request->filled('heure_spectacle'))
        ) {

            return redirect()
                ->back()
                ->with('error', 'Veuillez remplir le formulaire des artistes et/ou le formulaire des spectacles avec au moins un artiste et une heure.');
        }

        // Traitez le reste du code pour ajouter les artistes et les spectacles à la programmation

        return redirect()
            ->route('admin.index')
            ->with('success', 'Les artistes et/ou spectacles ont été ajoutés à la programmation');
    }
}
