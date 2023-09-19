<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Models\Propriete;
use App\Models\Alerte;
use App\Models\Visite;

class ProprieteController extends Controller
{
    //

    /**
     * Create a new AuthController instance.
     *
     * @return void
    */
    public function __construct() {
        $this->middleware('auth:api');
    }

    /**
     * Get All Propriete
     *
     * @return void
    */

    public function index(Request $request) {

        try {
            //code...

            $all = Propriete::all();

            return response()->json([
                "status" => true,
                "result" => $all
            ]);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 500);
        }

    }

    public function create(Request $request) {

        try {
            //code...

            $validator = Validator::make($request->all(), [
                'nom' => 'required',
                'prenom' => 'required',
                'typepropriete' => 'required',
                'adresse' => 'required',
                'codepostal' => 'required',
                'ville' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $appURL = env('APP_URL');

            $qrCode = base64_encode(QrCode::format('png')->size(500)->generate($appURL));
            $nom = $request->nom;
            $prenom = $request->prenom;
            $typepropriete = $request->typepropriete;
            $adresse = $request->adresse;
            $codepostal = $request->codepostal;
            $ville = $request->ville;

            $checkProprietaire = Propriete::where('nom', $nom)
            ->where('prenom', $prenom)
            ->where('typepropriete', $typepropriete)
            ->where('adresse', $adresse)
            ->where('codepostal', $codepostal)
            ->where('ville', $ville)
            ->exists();

            if($checkProprietaire){

                return response()->json([
                    "status" => false,
                    "message" => "Désolé, une propriété est déjà associé à ce propriétaire."
                ]);

            } else {

                $checkPropriete = Propriete::where('adresse', $adresse)
                ->where('codepostal', $codepostal)
                ->where('ville', $ville)
                ->exists();

                if($checkPropriete){
                    return response()->json([
                        "status" => false,
                        "message" => "Désolé, une propriété existe déjà."
                    ]);
                } else {

                    $addPropriete = Propriete::create([
                        "qrcode" => "data:image/png;base64,".$qrCode,
                        "nom" => $nom,
                        "prenom" => $prenom,
                        "typepropriete" => $typepropriete,
                        "adresse" => $adresse,
                        "codepostal" => $codepostal,
                        "ville" => $ville,
                        "status" => true
                    ]);

                }

            }

            return response()->json([
                "status" => true,
                "message" => "Votre propriété a été créer avec succès.",
                "result" => $addPropriete
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 500);
        }

    }

    /**
     * Show One By ID
     *
     * @return void
    */

    public function show(Request $request){

        try {
            //code...

            $validator = Validator::make($request->all(), [
                'id' => 'required'
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $id = $request->id;

            $getPropriete = Propriete::with('Alerte')->find($id);

            return response()->json([
                "status" => true,
                "result" => $getPropriete
            ]);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 500);
        }

    }

    /**
     * Update One By ID
     *
     * @return void
    */

    public function update(Request $request){

        try {
            //code...

            $validator = Validator::make($request->all(), [
                'id' => 'required'
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $id = $request->id;

            $propriete = Propriete::find($id);
            
            $nom = $request->nom !== null ? $request->nom : $propriete->nom;
            $prenom = $request->prenom !== null ? $request->prenom : $propriete->prenom;
            $adresse = $request->adresse !== null ? $request->adresse : $propriete->adresse;
            $codepostal = $request->codepostal !== null ? $request->codepostal : $propriete->codepostal;
            $ville = $request->ville !== null ? $request->ville : $propriete->ville;
            $status = $request->status !== null ? $request->status : $propriete->status;

            $updatePropriete = $propriete->update([
                "nom" => $nom,
                "prenom" => $prenom,
                "adresse" => $adresse,
                "codepostal" => $codepostal,
                "ville" => $ville,
                "status" => $status
            ]);

            $propriete = Propriete::find($id);

            return response()->json([
                "status" => true,
                "result" => $propriete
            ]);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 500);
        }

    }

    /**
     * Scan function
     *
     * @return void
    */

    public function scan(Request $request) {

        try {
            //code...
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'denomination' => 'required',
                'telephone' => 'required',
                'canaux' => 'required'
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $id = $request->id;
            $denomination = $request->denomination;
            $telephone = $request->telephone;
            $canaux = $request->canaux;

            $infosCanal = Alerte::find($canaux);

            switch ($infosCanal->type) {
                case 'email':
                    # code...
                    return response()->json([
                        "status" => true,
                        "message" => "L'email a bien été envoyer."
                    ]); 
                    break;
                case 'discord':
                    
                    $date = date('Y-m-d');

                    $checkSecurite = Visite::where('propriete_id', $id)
                    ->where(function ($query) use ($denomination, $telephone) {
                        $query->orWhere('denomination', $denomination)
                            ->orWhere('telephone', $telephone);
                    })
                    ->where('created_at', 'LIKE', '%'.$date.'%')
                    ->count();

                    if($checkSecurite >= 3){
                        return response()->json([
                            "status" => false,
                            "result" => $checkSecurite,
                            "message" => "Vous avez déjà sonné 3 fois. Revenez demain."
                        ]);
                    } else {

                        $createVisite = Visite::create([
                            "propriete_id" => $id,
                            "alerte_id" => $infosCanal->id,
                            "denomination" => $denomination,
                            "telephone" => $telephone,
                            "etat" => 1
                        ]);

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                        CURLOPT_URL => $infosCanal->informations,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS =>'{
                            "username": "ESONNETTE",
                            "content": "Quelqu\'un vient de sonner ! ['.$denomination.' / '.$telephone.']"
                        }',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                        ),
                        ));

                        $response = curl_exec($curl);

                        curl_close($curl);

                        return response()->json([
                            "status" => true,
                            "result" => $response
                        ]);

                    }

                    break;
                default:
                    # code...
                    break;
            }

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 500);
        }

    }

}
