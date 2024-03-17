<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Experience;
use App\Models\Certification;
use App\Models\Skill;
use App\Models\Education;
use App\Models\Project;
use App\Models\Bookmark;
use App\Models\Join;
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


class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function project($active, $take)
    {
        $data = DB::table('projects')
                ->where('is_active', $active)
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at');
    
        if ($take != 0) {
            $data->take($take);
        }
    
        $data = $data->get();
    
        $message = "Projects retrieved successfully.";
    
        $response = [
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function searchProject($search)
    {
        $data = DB::table('projects')
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('position', 'like', '%' . $search . '%')
                ->where('is_active', 1) 
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->get();
    
        if ($data->isEmpty()) {
            $message = "No projects found.";
        } else {
            $message = "Projects retrieved successfully.";
        }
    
        $response = [
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function meProject()
    {
        $userId = auth()->user()->id;
        
        $data = DB::table('projects')
                ->where('user_id', $userId)
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at');
    
        $data = $data->get();
    
        $message = "Your Projects retrieved successfully.";
    
        $response = [
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function detailProject($id)
    {
        $userId = auth()->user()->id;
        
        $data = Project::find($id);
    
        if (!$data) {
            return response()->json(['message' => 'Project not found', 'data' => []], 404);
        }
        
        $authorId = $data->user_id;
        
        // Check if the project is bookmarked by the user
        $isBookmarked = Bookmark::where('user_id', $userId)->where('project_id', $id)->exists();
    
        // Get the status_applied from the joins table based on project_id
        $statusApplied = Join::where('user_id', $userId)->where('project_id', $id)->value('status');
    
        // Get the user's data
        $user = User::find($authorId);
    
        if (!$user) {
            return response()->json(['message' => 'User not found', 'data' => []], 404);
        }
    
        // Additional data to include in the response
        $additionalData = [
            'is_bookmarked' => $isBookmarked ? 1 : 0,
            'status_applied' => $statusApplied ?? null, // null if not found
            'user_name' => $user->name,
            'user_photo' => $user->photo // Assuming 'photo' is the field containing the photo URL
        ];
    
        // Merge additional data with the original project data
        $responseData = array_merge($data->toArray(), $additionalData);
    
        $message = "Project detail successfully.";
        $response = [
            'message' => $message,
            'data' => $responseData
        ];
    
        return response()->json($response);
    }
    
    public function userJoin($projectId)
    {
        // Cek apakah project dengan $projectId ditemukan
        $projectExists = DB::table('projects')
                            ->where('id', $projectId)
                            ->exists();
        
        if (!$projectExists) {
            // Jika project tidak ditemukan, buat respons false
            $message = "Project not found";
            $response = [
                'success' => false,
                'message' => $message
            ];
            
            return response()->json($response);
        }
        
        // Ambil data pengguna yang memiliki project_id yang sama dari tabel joins
        $joinUsers = DB::table('joins')
                        ->join('users', 'joins.user_id', '=', 'users.id')
                        ->select('users.*', 'joins.status') // Pilih semua kolom pengguna dan status dari joins
                        ->where('joins.project_id', $projectId)
                        ->get();
        
        $message = "List User Join this project";
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $joinUsers
        ];
    
        return response()->json($response);
    }
    
    public function detailUser($userId, $projectId)
    {
        // Ambil data user dari tabel users berdasarkan userId
        $user = DB::table('users')->where('id', $userId)->first();
        
        if ($user) {
            // Periksa jika projectId bukan 0, maka ambil data join dari tabel joins berdasarkan userId dan projectId
            if ($projectId !== 0) {
                $join = DB::table('joins')->where('user_id', $userId)->where('project_id', $projectId)->first();
            } else {
                $join = null; // Jika projectId adalah 0, atur $join menjadi null
            }
            
            if ($join) {
                $message = 'User found with join data.';
                $success = true;
                // Menyatukan kolom status ke dalam data pengguna
                $user->status = $join->status;
            } else {
                $message = 'User found but no join data for the project.';
                $success = true;
                // Jika tidak ada data join, set status pengguna menjadi null
                $user->status = null;
            }
            
            // Menyimpan data pengguna ke dalam variabel $data
            $data = $user;
        } else {
            $message = 'User not found.';
            $success = false;
            $data = null;
        }
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        
        return response()->json($response);
    }

    
    public function reviewUserJoin($userId, $projectId,  $review)
    {
        // Ubah status pada entri di tabel 'joins' yang sesuai dengan projectId dan UserId
        $join = Join::where('project_id', $projectId)
                    ->where('user_id', $userId)
                    ->first();
    
        if ($join) {
            // Jika entri ditemukan, perbarui status sesuai dengan nilai $review
            $join->status = $review;
            $join->save();
            
            $success = true;
            $message = 'Review updated successfully.';
            $data = $join;
        } else {
            $success = false;
            $message = 'Join entry not found.';
            $data = null;
        }
    
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
    }
    
    public function addBookmark($id)
    {
        $userId = auth()->user()->id;
        
        // Mengecek apakah bookmark sudah ada untuk pengguna dan proyek tertentu
        $existingBookmark = Bookmark::where('user_id', $userId)->where('project_id', $id)->first();
    
        // Jika bookmark sudah ada, hapus bookmark dan kembalikan respons
        if ($existingBookmark) {
            $existingBookmark->delete();
            $message = "Bookmark removed successfully.";
        } else {
            // Membuat entri baru di tabel bookmarks
            Bookmark::create([
                'user_id' => $userId,
                'project_id' => $id
            ]);
            $message = "Bookmark added successfully.";
        }
    
        $response = [
            'success' => true,
            'message' => $message,
        ];
    
        return response()->json($response);
    }
    
    public function bookmarkProject()
    {
        $userId = auth()->user()->id;
        
        // Mencari bookmark berdasarkan ID pengguna
        $bookmarks = Bookmark::where('user_id', $userId)->get();
    
        // Jika tidak ada bookmark yang ditemukan, kembalikan respons false
        if ($bookmarks->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data not found', 'data' => []]);
        }
    
        // Mendapatkan ID proyek dari bookmark yang ditemukan
        $projectIds = $bookmarks->pluck('project_id')->toArray();
        
        // Mengambil data proyek yang sesuai dengan ID proyek yang ditemukan dalam bookmark
        $projects = Project::whereIn('id', $projectIds)->orderBy('updated_at', 'desc')->get();
    
        // Menambahkan is_bookmarked menjadi 1 untuk setiap proyek yang ditandai oleh pengguna
        $projects->each(function ($project) use ($bookmarks) {
            $project->is_bookmarked = $bookmarks->contains('project_id', $project->id) ? 1 : 0;
        });
    
        $message = "Bookmarked projects retrieved successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $projects
        ];
    
        return response()->json($response);
    }
    
    public function joinProject($id)
    {
        $userId = auth()->user()->id;
        
        $existingJoin = Join::where('user_id', $userId)->where('project_id', $id)->first();
    
        if ($existingJoin) {
            $existingJoin->delete();
            $message = "Cancel Join Project successfully.";
        } else {
            Join::create([
                'user_id' => $userId,
                'project_id' => $id,
                'status' => 0
            ]);
            $message = "Join Project successfully.";
        }
    
        $response = [
            'success' => true,
            'message' => $message,
        ];
    
        return response()->json($response);
    }
    
    public function reviewProject($projectId, $review)
    {
        $userId = auth()->user()->id;
        
        // Memperbarui kolom is_active berdasarkan $review
        $project = Project::findOrFail($projectId);
        $project->is_active = $review;
        $project->save();
        
        Join::where('project_id', $projectId)
            ->where('status', 0)
            ->update(['status' => $review]);
        
        $message = "Project review updated successfully.";
    
        $response = [
            'success' => true,
            'message' => $message,
        ];
    
        return response()->json($response);
    }
    
    public function joinedProject()
    {
        $userId = auth()->user()->id;
        
        $join = Join::where('user_id', $userId)->orderByDesc('updated_at')
                ->orderByDesc('created_at')->get();
    
        if ($join->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No projects have been applied'], 404);
        }
    
        // Mendapatkan ID proyek dari bookmark yang ditemukan
        $projectIds = $join->pluck('project_id')->toArray();
        
        // Mengambil data proyek yang sesuai dengan ID proyek yang ditemukan dalam bookmark
        $projects = Project::whereIn('id', $projectIds)->orderByDesc('updated_at')
                ->orderByDesc('created_at')->get();
    
        // Menambahkan is_bookmarked menjadi 1 untuk setiap proyek yang ditandai oleh pengguna
        $projects->each(function ($project) use ($join) {
            $status = $join->where('project_id', $project->id)->first()->status;
            $project->status_applied = $status;
        });
    
        $message = "List applied projects retrieved successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $projects
        ];
    
        return response()->json($response);
    }
    
    public function addProject(Request $request)
    {
        $userId = auth()->user()->id;
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'name' => 'required|string',
            'position' => 'required|string',
            'location' => 'required|string',
            'tipe' => 'required|string',
            'is_paid' => 'required|boolean',
            'time' => 'required|string',
            'description' => 'required|string',
            'requirements' => 'required|string',
        ]);

        if($userId == 1 || $validatedData['is_paid'] == false){
            $isActive = 1;
        }else{
            $isActive = 0;
        }
    
        // Tambahkan data pengalaman baru ke dalam database
        $data = Project::create([
            'name' => $validatedData['name'],
            'position' => $validatedData['position'],
            'location' => $validatedData['location'],
            'tipe' => $validatedData['tipe'],
            'is_paid' => $validatedData['is_paid'] ?? false,
            'time' => $validatedData['time'],
            'description' => $validatedData['description'],
            'requirements' => $validatedData['requirements'],
            'user_id' => $userId,
            'is_active' => $isActive
        ]);
    
        // Buat respons
        $message = "Project added successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    
        return response()->json($response);
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
