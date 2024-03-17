<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Experience;
use App\Models\Certification;
use App\Models\Skill;
use App\Models\Education;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;


class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function me()
    {
        $user = auth()->user();
        
        // Check if any of the specified fields are null
        $fieldsToCheck = ['photo', 'summary', 'resume', 'about'];
        $isNull = false;
        foreach ($fieldsToCheck as $field) {
            if (is_null($user->$field)) {
                $isNull = true;
                break;
            }
        }
    
        // If any field is null, update is_complete to false
        if ($isNull) {
            $user->is_complete = false;
        }else{
            $user->is_complete = true;
        }
        
        $user->save();
        
        return response()->json([
            'message' => 'Berhasil mendapatkan data user',
            'data' => $user
        ], Response::HTTP_OK);
    }

    public function editProfile(Request $request)
    {
        // Dapatkan pengguna yang sedang masuk
        $user = auth()->user();
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'name' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,JPG|max:5120', // Maksimum 5MB
            'summary' => 'nullable|string',
            'linkedin' => 'nullable|string',
            'github' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'instagram' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf|max:5120', // Maksimum 5MB
        ]);
        
        // Update data profil pengguna
        $user->name = $validatedData['name'];
        $user->summary = $validatedData['summary'];
        $user->linkedin = $this->generateLinkedInUrl($validatedData['linkedin']);
        $user->github = $this->generateGitHubUrl($validatedData['github']);
        $user->whatsapp = $this->generateWhatsAppUrl($validatedData['whatsapp']);
        $user->instagram = $this->generateInstagramUrl($validatedData['instagram']);
    
        // Menghapus foto dan resume sebelumnya jika ada
        if ($request->hasFile('photo') && $user->photo) {
            // Hapus foto sebelumnya
            $path = 'public/images/profile/' . basename($user->photo);
            Storage::delete($path);
            $user->photo = null;
        }
    
        if ($request->hasFile('resume') && $user->resume) {
            // Hapus resume sebelumnya
            $path = 'public/file/resume/' . basename($user->resume);
            Storage::delete($path);
            $user->resume = null;
        }
    
        // Update data profil pengguna
        if ($request->hasFile('resume')) {
            $resume = $request->file('resume');
            $filename = Str::random(40) . '.' . $resume->getClientOriginalExtension();
            $path = 'file/resume/' . $filename;
            $resume->storeAs('public', $path);
            $user->resume = 'https://api.sukacolab.com' . Storage::url($path);
        }

        // Jika ada file foto yang diunggah, simpan dan perbarui link foto pengguna
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = Str::random(40) . '.' . $photo->getClientOriginalExtension();
            $path = 'images/profile/' . $filename;
            $photo->storeAs('public', $path);
            $user->photo = 'https://api.sukacolab.com' . Storage::url($path);
        }
        
        $user->save();
    
        // Buat respons
        $message = "Profile updated successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $user
        ];
    
        return response()->json($response);
    }
    
    public function editPhoto(Request $request)
    {
        // Dapatkan pengguna yang sedang masuk
        $user = auth()->user();
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,JPG|max:5120', // Maksimum 5MB
        ]);

    
        // Menghapus foto dan resume sebelumnya jika ada
        if ($request->hasFile('photo') && $user->photo) {
            // Hapus foto sebelumnya
            $path = 'public/images/profile/' . basename($user->photo);
            Storage::delete($path);
            $user->photo = null;
            $user->save();
        }

        // Jika ada file foto yang diunggah, simpan dan perbarui link foto pengguna
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = Str::random(40) . '.' . $photo->getClientOriginalExtension();
            $path = 'images/profile/' . $filename;
            $photo->storeAs('public', $path);
            $user->photo = 'https://api.sukacolab.com' . Storage::url($path);
            $user->save();
        } else{
            // Buat respons
            $message = "No photos uploaded.";
            $response = [
                'success' => false,
                'message' => $message
            ];
        
            return response()->json($response);
        }
    
        // Buat respons
        $message = "Photo updated successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $user
        ];
    
        return response()->json($response);
    }
    
    public function editResume(Request $request)
    {
        // Dapatkan pengguna yang sedang masuk
        $user = auth()->user();
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'resume' => 'nullable|file|mimes:pdf|max:5120', // Maksimum 5MB
        ]);
    
        if ($request->hasFile('resume') && $user->resume) {
            // Hapus resume sebelumnya
            $path = 'public/file/resume/' . basename($user->resume);
            Storage::delete($path);
            $user->resume = null;
            $user->save();
        }
    
        // Update data profil pengguna
        if ($request->hasFile('resume')) {
            $resume = $request->file('resume');
            $filename = Str::random(40) . '.' . $resume->getClientOriginalExtension();
            $path = 'file/resume/' . $filename;
            $resume->storeAs('public', $path);
            $user->resume = 'https://api.sukacolab.com' . Storage::url($path);
            $user->save();
        }else{
            // Buat respons
            $message = "No resume uploaded.";
            $response = [
                'success' => false,
                'message' => $message
            ];
        
            return response()->json($response);
        }
        
        $message = "Resume updated successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $user
        ];
    
        return response()->json($response);
    }
    
    public function editAbout(Request $request)
    {
        // Dapatkan pengguna yang sedang masuk
        $user = auth()->user();
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'about' => 'required|string',
        ]);
    
        $user->about = $validatedData['about'];
        
        $user->save();
    
        // Buat respons
        $message = "About updated successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $user
        ];
    
        return response()->json($response);
    }

    private function generateLinkedInUrl($username)
    {
        if (!empty($username)) {
            return 'https://www.linkedin.com/in/' . $username;
        }
        return null;
    }
    
    private function generateGitHubUrl($username)
    {
        if (!empty($username)) {
            return 'https://github.com/' . $username;
        }
        return null;
    }
    
    private function generateWhatsAppUrl($phoneNumber)
    {
        if (!empty($phoneNumber)) {
            // Menghapus karakter selain angka
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            // Jika nomor dimulai dengan '08', ubah menjadi '628'
            if (substr($phoneNumber, 0, 2) === '08') {
                $phoneNumber = '628' . substr($phoneNumber, 2);
            }
            
            return 'https://wa.me/' . $phoneNumber;
        }
        return null;
    }

    private function generateInstagramUrl($username)
    {
        if (!empty($username)) {
            return 'https://www.instagram.com/' . $username;
        }
        return null;
    }

    public function experience($take, $userId)
    {
        if($userId==0){
            $userId = auth()->user()->id;   
        }
        
        if($take==0){
            $num = null;
        }else{
            $num = $take;
        }

        $experiences = DB::table('experiences')
                ->where('user_id', $userId)
                ->orderByDesc('is_now')
                ->orderByDesc('end_date')
                ->take($num)
                ->get();

        $message = "Experiences retrieved successfully.";
    
        // Menggabungkan pesan dan data ke dalam respons JSON
        $response = [
            'message' => $message,
            'data' => $experiences
        ];
    
        return response()->json($response);
    }

    public function certification($take, $userId)
    {
        if($userId==0){
            $userId = auth()->user()->id;   
        }
        
        if($take==0){
            $num = null;
        }else{
            $num = $take;
        }

        $certifications = DB::table('certifications')
                ->where('user_id', $userId)
                ->orderByDesc('publish_date')
                ->take($num)
                ->get();

        $message = "Certifications retrieved successfully.";
    
        // Menggabungkan pesan dan data ke dalam respons JSON
        $response = [
            'message' => $message,
            'data' => $certifications
        ];
    
        return response()->json($response);
    }

    public function skill($take, $userId)
    {
        if($userId==0){
            $userId = auth()->user()->id;   
        }
        
        if($take==0){
            $num = null;
        }else{
            $num = $take;
        }

        $skills = DB::table('skills')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->take($num)
                ->get();

        $message = "Skills retrieved successfully.";
    
        // Menggabungkan pesan dan data ke dalam respons JSON
        $response = [
            'message' => $message,
            'data' => $skills
        ];
    
        return response()->json($response);
    }
    
    public function education($take, $userId)
    {
        if($userId==0){
            $userId = auth()->user()->id;   
        }
        
        if($take==0){
            $num = null;
        }else{
            $num = $take;
        }

        $educations = DB::table('educations')
                ->where('user_id', $userId)
                ->orderByDesc('is_now')
                ->orderByDesc('start_date')
                ->take($num)
                ->get();

        $message = "Educations retrieved successfully.";
    
        // Menggabungkan pesan dan data ke dalam respons JSON
        $response = [
            'message' => $message,
            'data' => $educations
        ];
    
        return response()->json($response);
    }
    
    public function detailExperience($id)
    {
        // Ambil pengalaman yang ingin diubah berdasarkan ID
        $data = Experience::find($id);
    
        // Jika pengalaman tidak ditemukan, kembalikan respons 404 Not Found
        if (!$data) {
            return response()->json(['message' => 'Experience not found', 'data' => []], 404);
        }
        
        // Buat respons
        $message = "Experience detail successfully.";
        $response = [
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }

    public function detailCertification($id)
    {
        // Ambil pengalaman yang ingin diubah berdasarkan ID
        $data = Certification::find($id);
    
        // Jika pengalaman tidak ditemukan, kembalikan respons 404 Not Found
        if (!$data) {
            return response()->json(['message' => 'Certification not found', 'data' => []], 404);
        }
        
        // Buat respons
        $message = "Certification detail successfully.";
        $response = [
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }

    public function detailSkill($id)
    {
        // Ambil pengalaman yang ingin diubah berdasarkan ID
        $data = Skill::find($id);
    
        // Jika pengalaman tidak ditemukan, kembalikan respons 404 Not Found
        if (!$data) {
            return response()->json(['message' => 'Skill not found', 'data' => []], 404);
        }
        
        // Buat respons
        $message = "Skill detail successfully.";
        $response = [
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function detailEducation($id)
    {
        // Ambil pengalaman yang ingin diubah berdasarkan ID
        $data = Education::find($id);
    
        // Jika pengalaman tidak ditemukan, kembalikan respons 404 Not Found
        if (!$data) {
            return response()->json(['message' => 'Education not found', 'data' => []], 404);
        }
        
        // Buat respons
        $message = "Education detail successfully.";
        $response = [
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
     public function allExperience()
    {
        $userId = auth()->user()->id;

        $experiences = DB::table('experiences')
                ->where('user_id', $userId)
                ->orderByDesc('is_now')
                ->orderByDesc('end_date')
                ->get();

        $message = "Experiences retrieved successfully.";
    
        // Menggabungkan pesan dan data ke dalam respons JSON
        $response = [
            'message' => $message,
            'data' => $experiences
        ];
    
        return response()->json($response);
    }

    public function allCertification()
    {
        $userId = auth()->user()->id;

        $certifications = DB::table('certifications')
                ->where('user_id', $userId)
                ->orderByDesc('publish_date')
                ->get();

        $message = "Certifications retrieved successfully.";
    
        // Menggabungkan pesan dan data ke dalam respons JSON
        $response = [
            'message' => $message,
            'data' => $certifications
        ];
    
        return response()->json($response);
    }

    public function allSkill()
    {
        $userId = auth()->user()->id;

        $skills = DB::table('skills')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->get();

        $message = "Skills retrieved successfully.";
    
        // Menggabungkan pesan dan data ke dalam respons JSON
        $response = [
            'message' => $message,
            'data' => $skills
        ];
    
        return response()->json($response);
    }
    
    public function allEducation()
    {
        $userId = auth()->user()->id;

        $educations = DB::table('educations')
                ->where('user_id', $userId)
                ->orderByDesc('is_now')
                ->orderByDesc('start_date')
                ->get();

        $message = "Educations retrieved successfully.";
    
        // Menggabungkan pesan dan data ke dalam respons JSON
        $response = [
            'message' => $message,
            'data' => $educations
        ];
    
        return response()->json($response);
    }
    
    public function addExperience(Request $request)
    {
        $userId = auth()->user()->id;
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'title' => 'required|string',
            'company' => 'required|string',
            'role' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_now' => 'nullable|boolean',

        ]);
    
        // Tambahkan data pengalaman baru ke dalam database
        $data = Experience::create([
            'user_id' => $userId,
            'title' => $validatedData['title'],
            'company' => $validatedData['company'],
            'role' => $validatedData['role'],
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
            'is_now' => $validatedData['is_now'] ?? false,

        ]);
    
        // Buat respons
        $message = "Experience added successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function addCertification(Request $request)
    {
        $userId = auth()->user()->id;
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'name' => 'required|string',
            'publisher' => 'required|string',
            'credential' => 'nullable|string',
            'publish_date' => 'required|date',
            'expire_date' => 'required|date',

        ]);
    
        // Tambahkan data pengalaman baru ke dalam database
        $data = Certification::create([
            'user_id' => $userId,
            'name' => $validatedData['name'],
            'publisher' => $validatedData['publisher'],
            'credential' => $validatedData['credential'],
            'publish_date' => $validatedData['publish_date'],
            'expire_date' => $validatedData['expire_date'],

        ]);
    
        // Buat respons
        $message = "Certification added successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function addSkill(Request $request)
    {
        $userId = auth()->user()->id;
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',

        ]);
    
        // Tambahkan data pengalaman baru ke dalam database
        $data = Skill::create([
            'user_id' => $userId,
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],

        ]);
    
        // Buat respons
        $message = "Skill added successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function addEducation(Request $request)
    {
        $userId = auth()->user()->id;
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'instansi' => 'required|string',
            'major' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_now' => 'nullable|boolean',

        ]);
    
        // Tambahkan data pengalaman baru ke dalam database
        $data = Education::create([
            'user_id' => $userId,
            'instansi' => $validatedData['instansi'],
            'major' => $validatedData['major'],
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
            'is_now' => $validatedData['is_now'] ?? false,
        ]);
    
        // Buat respons
        $message = "Education added successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function editExperience(Request $request, $id)
    {
        // Ambil pengalaman yang ingin diubah berdasarkan ID
        $experience = Experience::find($id);
    
        // Jika pengalaman tidak ditemukan, kembalikan respons 404 Not Found
        if (!$experience) {
            return response()->json(['success' => false, 'message' => 'Experience not found']);
        }
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'title' => 'required|string',
            'company' => 'required|string',
            'role' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_now' => 'nullable|boolean',
        ]);
    
        // Update data pengalaman
        $experience->title = $validatedData['title'];
        $experience->company = $validatedData['company'];
        $experience->role = $validatedData['role'];
        $experience->start_date = $validatedData['start_date'];
        $experience->end_date = $validatedData['end_date'];
        $experience->is_now = $validatedData['is_now'] ?? false;
        $experience->save();
    
        // Buat respons
        $message = "Experience updated successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $experience
        ];
    
        return response()->json($response);
    }
    
    public function editCertification(Request $request, $id)
    {
        // Ambil sertifikasi yang ingin diubah berdasarkan ID
        $certification = Certification::find($id);
    
        // Jika sertifikasi tidak ditemukan, kembalikan respons 404 Not Found
        if (!$certification) {
            return response()->json(['success' => false, 'message' => 'Certification not found']);
        }
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'name' => 'required|string',
            'publisher' => 'required|string',
            'credential' => 'nullable|string',
            'publish_date' => 'required|date',
            'expire_date' => 'required|date',
        ]);
    
        // Update data sertifikasi
        $certification->name = $validatedData['name'];
        $certification->publisher = $validatedData['publisher'];
        $certification->credential = $validatedData['credential'];
        $certification->publish_date = $validatedData['publish_date'];
        $certification->expire_date = $validatedData['expire_date'];
        $certification->save();
    
        // Buat respons
        $message = "Certification updated successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $certification
        ];
    
        return response()->json($response);
    }
    
    public function editSkill(Request $request, $id)
    {
        // Ambil skill yang ingin diubah berdasarkan ID
        $skill = Skill::find($id);
    
        // Jika skill tidak ditemukan, kembalikan respons 404 Not Found
        if (!$skill) {
            return response()->json(['success' => false, 'message' => 'Skill not found']);
        }
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);
    
        // Update data skill
        $skill->name = $validatedData['name'];
        $skill->description = $validatedData['description'];
        $skill->save();
    
        // Buat respons
        $message = "Skill updated successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $skill
        ];
    
        return response()->json($response);
    }
    
    public function editEducation(Request $request, $id)
    {
        // Ambil pendidikan yang ingin diubah berdasarkan ID
        $education = Education::find($id);
    
        // Jika pendidikan tidak ditemukan, kembalikan respons 404 Not Found
        if (!$education) {
            return response()->json(['success' => false, 'message' => 'Education not found']);
        }
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'instansi' => 'required|string',
            'major' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_now' => 'nullable|boolean',
        ]);
    
        // Update data pendidikan
        $education->instansi = $validatedData['instansi'];
        $education->major = $validatedData['major'];
        $education->start_date = $validatedData['start_date'];
        $education->end_date = $validatedData['end_date'];
        $education->is_now = $validatedData['is_now'] ?? false;
        $education->save();
    
        // Buat respons
        $message = "Education updated successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $education
        ];
    
        return response()->json($response);
    }
    
    public function deleteExperience($id)
    {
        // Temukan pengalaman yang ingin dihapus
        $experience = Experience::find($id);
    
        // Jika tidak ditemukan, kembalikan respons 404 Not Found
        if (!$experience) {
            return response()->json(['success' => false, 'message' => 'Experience not found']);
        }
    
        // Hapus pengalaman
        $experience->delete();
    
        // Buat respons
        $message = "Experience deleted successfully.";
        return response()->json(['success' => true, 'message' => $message]);
    }
    
    public function deleteCertification($id)
    {
        // Temukan sertifikasi yang ingin dihapus
        $certification = Certification::find($id);
    
        // Jika tidak ditemukan, kembalikan respons 404 Not Found
        if (!$certification) {
            return response()->json(['success' => false, 'message' => 'Certification not found']);
        }
    
        // Hapus sertifikasi
        $certification->delete();
    
        // Buat respons
        $message = "Certification deleted successfully.";
        return response()->json(['success' => true, 'message' => $message]);
    }
    
    public function deleteSkill($id)
    {
        // Temukan skill yang ingin dihapus
        $skill = Skill::find($id);
    
        // Jika tidak ditemukan, kembalikan respons 404 Not Found
        if (!$skill) {
            return response()->json(['success' => false, 'message' => 'Skill not found']);
        }
    
        // Hapus skill
        $skill->delete();
    
        // Buat respons
        $message = "Skill deleted successfully.";
        return response()->json(['success' => true, 'message' => $message]);
    }
    
    public function deleteEducation($id)
    {
        // Temukan pendidikan yang ingin dihapus
        $education = Education::find($id);
    
        // Jika tidak ditemukan, kembalikan respons 404 Not Found
        if (!$education) {
            return response()->json(['success' => false, 'message' => 'Education not found']);
        }
    
        // Hapus pendidikan
        $education->delete();
    
        // Buat respons
        $message = "Education deleted successfully.";
        return response()->json(['success' => true, 'message' => $message]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'response' => Response::HTTP_OK,
            'success' => true,
            'message' => 'JWT Token refresh Successfully',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ], Response::HTTP_OK);
    }

    protected function respondWithTokenLogin($token, $userId)
    {
        return response()->json([
            'message' => 'Login Berhasil',
            'data' => [
                'user_id' => $userId,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ], Response::HTTP_OK);
    }
}
