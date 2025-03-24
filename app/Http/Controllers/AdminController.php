<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function AdminDashboard()
    {

        return view('admin.index');
    } // End Method

    public function AdminLogout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $notification = array(
            'message' => 'Logout Successfully',
            'alert-type' => 'info'
        );

        return redirect('/admin/login')->with($notification);
    } //End method

    public function AdminLogin()
    {
        return view('admin.admin_login');
    } // End method

    public function AdminProfile()
    {
        $id = Auth::user()->id;
        $profileData = User::find($id);
        return view('admin.admin_profile_view', compact('profileData'));
    } // End method

    public function AdminProfileStore(Request $request)
    {
        $id = Auth::user()->id;
        $data = User::find($id);
        $data->name = $request->name;
        $data->username = $request->username;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->address = $request->address;

        if ($request->file('photo')) {
            $file = $request->file('photo');
            @unlink(public_path('upload/admin_images/' . $data->photo));
            $filename = date('YmdHi') . $file->getClientOriginalName();
            $file->move(public_path('upload/admin_images'), $filename);
            $data['photo'] = $filename;
        }

        $data->save();

        $notification = array(
            'message' => 'Admin Profile Uploaded Successfully',
            'alert-type' => 'success',
        );

        return redirect()->back()->with($notification);
    } // End method

    public function AdminChangePassword(Request $request)
    {
        $id = Auth::user()->id;
        $profileData = User::find($id);
        return view('admin.admin_change_password', compact('profileData'));
    } // End method

    public function AdminPasswordUpdate(Request $request)
    {
        // dd($request);
        // Validation
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed'
        ]);
        if (!Hash::check($request->old_password, auth::user()->password)) {
            $notification = array(
                'message' => 'Old password does not match',
                'alert-type' => 'error',
            );
            return back()->with($notification);
        }

        /// update the new password
        User::whereId(auth::user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);
        $notification = array(
            'message' => 'Password Updated successfully',
            'alert-type' => 'success',
        );
        return back()->with($notification);
    } // End Method

    public function BecomeIntructor()
    {
        return view('frontend.intructor.reg_instructor');
    } // End Method

    public function InstructorRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'unique:users'],
        ]);
        User::insert([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'password' => Hash::make($request->password),
            'role' => 'instructor',
            'status' => '0'
        ]);
        $notification = array(
            'message' => 'Instructor registered successfully',
            'alert-type' => 'success',
        );
        return redirect()->route('instructor.login')->with($notification);
    } // End Method

    public function AllInstructor()
    {
        $allinstructor = User::where('role', 'instructor')->latest()->get();

        return view('admin.backend.instructor.all_instructor', compact('allinstructor'));
    } // End Method

    public function UpdateUserStatus(Request $request)
    {

        $userId = $request->input('user_id');
        $isChecked = $request->input('is_checked', 0);

        $user = User::find($userId);
        if ($user) {
            $user->status = $isChecked;
            $user->save();
        }

        return response()->json(['message' => 'User Status Updated Successfully']);
    } // End Method

    public function AdminAllCourse()
    {
        $course = Course::latest()->get();
        return view('admin.backend.courses.all_course', compact('course'));
    } // End Method

    public function UpdateCourseStatus(Request $request)
    {

        $courseId = $request->input('course_id');
        $isChecked = $request->input('is_checked', 0);

        $course = Course::find($courseId);
        if ($course) {
            $course->status = $isChecked;
            $course->save();
        }

        return response()->json(['message' => 'Course Status Updated Successfully']);
    } // End Method

    public function AdminCourseDetails($id)
    {
        $course = Course::find($id);
        return view('admin.backend.courses.course_details', compact('course'));
    } // End Method

    /// Admin User All Method ////////////

    public function AllAdmin()
    {

        $alladmin = User::where('role', 'admin')->get();
        return view('admin.backend.pages.admin.all_admin', compact('alladmin'));
    } // End Method

    public function AddAdmin()
    {

        $roles = Role::all();
        return view('admin.backend.pages.admin.add_admin', compact('roles'));
    } // End Method

    public function StoreAdmin(Request $request)
    {

        $user = new User();
        $user->username = $request->username;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->password = Hash::make($request->password);
        $user->role = 'admin';
        $user->status = '1';
        $user->save();

        if ($request->roles) {
            $roleName = Role::where('id', $request->roles)->value('name');

            if ($roleName) {
                $user->assignRole($roleName);
            }
        }
        $notification = array(
            'message' => 'New Admin Inserted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('all.admin')->with($notification);
    } // End Method
    public function EditAdmin($id)
    {

        $user = User::find($id);
        $roles = Role::all();
        return view('admin.backend.pages.admin.edit_admin', compact('user', 'roles'));
    } // End Method

    public function UpdateAdmin(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('all.admin')->with([
                'message' => 'Admin not found!',
                'alert-type' => 'error'
            ]);
        }

        // Update user details
        $user->username = $request->username;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->role = 'admin';
        $user->status = '1';
        $user->save();

        // Sync roles properly
        if ($request->roles) {
            $roleName = Role::where('id', $request->roles)->value('name');

            if ($roleName) {
                $user->syncRoles([$roleName]); // Replaces previous roles
            }
        } else {
            $user->syncRoles([]); // Remove all roles if none are provided
        }

        return redirect()->route('all.admin')->with([
            'message' => 'Admin Updated Successfully',
            'alert-type' => 'success'
        ]);
    }
    // End Method

    public function DeleteAdmin($id)
    {

        $user = User::find($id);
        if (!is_null($user)) {
            $user->delete();
        }

        $notification = array(
            'message' => 'Admin Deleted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
    } // End Method
}
