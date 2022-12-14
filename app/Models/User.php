<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
        use Notifiable,HasApiTokens;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'avatar',
        'created_by',
        'phone',
        'dob',
        'gender',
        'skills',
        'is_active',
        'lang',
        'facebook',
        'whatsapp',
        'instagram',
        'likedin',
        'mode',
        'is_trial_done',
        'interested_plan_id',
        'is_register_trial',
        'plan',
        'plan_expire_date',
        'details',
        'requested_plan',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Change image while fetching
    protected $appends = [''];

    public function getImgAvatarAttribute()
    {
        if(\Storage::exists($this->avatar) && !empty($this->avatar))
        {
            return $this->attributes['img_avatar'] = 'src=' . asset(\Storage::url($this->avatar));
        }
        else
        {
            return $this->attributes['img_avatar'] = 'avatar=' . $this->name;
        }
    }

    public function getCreatedBy()
    {
        if($this->type == 'owner')
        {
            return $this->id;
        }
        else
        {
            return $this->created_by;
        }
    }

    public function creatorId()
    {
        if($this->type == 'owner' || $this->type == 'super admin')
        {
            return $this->id;
        }
        else
        {
            return $this->created_by;
        }
    }

    public function projects()
    {
        return $this->belongsToMany('App\Models\Project', 'project_users', 'user_id', 'project_id')->withPivot('id', 'permission')->withTimestamps();
    }

    public function todo()
    {
        return $this->hasMany('App\Models\UserToDo', 'user_id', 'id');
    }

    // Get Open Task and Last Seven Days Timesheet Logged Hours
    public function usrCommonData()
    {
        $user_projects = $this->projects()->pluck('project_id')->toArray();

        // get Open Task
        $open_task = ProjectTask::whereIn('project_id', $user_projects)->where('is_complete', '=', 0)->whereRaw("find_in_set('" . $this->id . "',assign_to)")->count();

        // Get Last Seven Days Timesheet with date
        $seven_days = Utility::getLastSevenDays();
        $chart_data = [];
        foreach($seven_days as $date => $day)
        {
            $time             = Timesheet::where('created_by', '=', $this->id)->where('date', 'LIKE', $date)->pluck('time')->toArray();
            $chart_data[$day] = str_replace(':', '.', Utility::calculateTimesheetHours($time));
        }

        return [
            'open_task' => $open_task,
            'timesheet' => $chart_data,
        ];
    }

    // Get task users
    public function tasks()
    {
        return ProjectTask::whereRaw("find_in_set('" . $this->id . "',assign_to)")->get();
    }

    // Get User's Contact
    public function contacts()
    {
        return $this->hasMany('App\Models\UserContact', 'parent_id', 'id');
    }

    // For Email template Module
    public function defaultEmail()
    {
        // Email Template
        $emailTemplate = [
            'User Invite' => 'Email : {email},Password : {password}',
            'Invite Project' => 'Project Name : {project_name},Project Status : {project_status},Project Budget : {project_budget},Project Hours : {project_hours}',
            'Task Assign' => 'Task Name : {task_name},Task Priority : {task_priority},Task Project : {task_project},Task Stage : {task_stage}',
            'Create Timesheet' => 'Timesheet Project : {timesheet_project},Timesheet Task : {timesheet_task},Timesheet Time : {timesheet_time},Timesheet Date : {timesheet_date}',
        ];

        foreach($emailTemplate as $eTemp => $keyword)
        {
            EmailTemplate::create([
                                      'name' => $eTemp,
                                      'from' => env('APP_NAME'),
                                      'keyword' => $keyword,
                                      'created_by' => $this->id,
                                  ]);
        }

        // Make content for email template language
        $defaultTemplate = [
            'User Invite' => [
                'subject' => 'Login Detail',
                'lang' => [
                    'ar' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">????????????&nbsp;<br>?????????? ???? ???? {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">???????????? ????????????????????&nbsp;</span>: {email}<br><span style="font-weight: bolder;">???????? ????????</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">????????<br>{app_name}</p>',
                    'da' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Hej,&nbsp;<br>Velkommen til {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">E-mail&nbsp;</span>: {email}<br><span style="font-weight: bolder;">Adgangskode</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Tak,<br>{app_name}</p>',
                    'de' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Hallo,&nbsp;<br>Willkommen zu {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">Email&nbsp;</span>: {email}<br><span style="font-weight: bolder;">Passwort</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Vielen Dank,<br>{app_name}</p>',
                    'en' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Hello,&nbsp;<br>Welcome to {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">Email&nbsp;</span>: {email}<br><span style="font-weight: bolder;">Password</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Thanks,<br>{app_name}</p>',
                    'es' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Hola,&nbsp;<br>Bienvenido a {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">Email&nbsp;</span>: {email}<br><span style="font-weight: bolder;">Contrase??a</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Gracias,<br>{app_name}</p>',
                    'fr' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Bonjour,&nbsp;<br>Bienvenue ?? {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">Email&nbsp;</span>: {email}<br><span style="font-weight: bolder;">Mot de passe</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Merci,<br>{app_name}</p>',
                    'it' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Ciao,&nbsp;<br>Benvenuto a {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">E-mail&nbsp;</span>: {email}<br><span style="font-weight: bolder;">Parola d\'ordine</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Grazie,<br>{app_name}</p>',
                    'ja' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">??????????????????&nbsp;<br>??????????????? {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">E?????????&nbsp;</span>: {email}<br><span style="font-weight: bolder;">???????????????</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">???????????????<br>{app_name}</p>',
                    'nl' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Hallo,&nbsp;<br>Welkom bij {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">E-mail&nbsp;</span>: {email}<br><span style="font-weight: bolder;">Wachtwoord</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Bedankt,<br>{app_name}</p>',
                    'pl' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Dzie?? dobry,&nbsp;<br>Witamy w {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">E-mail&nbsp;</span>: {email}<br><span style="font-weight: bolder;">Has??o</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">Dzi??ki,<br>{app_name}</p>',
                    'ru' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">????????????,&nbsp;<br>?????????? ???????????????????? ?? {app_name}.</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-weight: bolder;">????. ??????????&nbsp;</span>: {email}<br><span style="font-weight: bolder;">????????????</span>&nbsp;: {password}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">{app_url}</p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;">??????????????,<br>{app_name}</p>',
                ],
            ],
            'Invite Project' => [
                'subject' => 'New Project Assign',
                'lang' => [
                    'ar' => '<p>????????????</p><p>???? ?????????? ?????????? ???????? ????.</p><p><b>?????? ?????????????? </b>: {project_name}<br><b>???????? ?????????????? </b>:<b>&nbsp;</b>{project_status}<br><b>?????????????? ?????????????? </b>:<b> </b>{project_budget}<br><b>?????????? ?????????????? </b>:<b> </b>{project_hours}</p>',
                    'da' => '<p>Hej,</p><p>Der er tildelt nyt projekt til dig.</p><p><b>Projekt navn </b>: {project_name}<br><b>Projektstatus </b>:<b>&nbsp;</b>{project_status}<br><b>Projektbudget </b>:<b> </b>{project_budget}<br><b>Projekt timer </b>:<b> </b>{project_hours}</p>',
                    'de' => '<p>Hallo,</p><p>Ihnen wurde ein neues Projekt zugewiesen.</p><p><b>Projektname </b>: {project_name}<br><b>Projekt-Status </b>:<b>&nbsp;</b>{project_status}<br><b>Projektbudget </b>:<b> </b>{project_budget}<br><b>Projektstunden </b>:<b> </b>{project_hours}</p>',
                    'en' => '<p>Hello,</p><p>New Project has been assigned to you.</p><p><b>Project Name </b>: {project_name}<br><b>Project Status </b>:<b>&nbsp;</b>{project_status}<br><b>Project Budget </b>:<b> </b>{project_budget}<br><b>Project Hours </b>:<b> </b>{project_hours}</p>',
                    'es' => '<p>Hola,</p><p>Se le ha asignado un nuevo proyecto.</p><p><b>Nombre del proyecto </b>: {project_name}<br><b>Estado del proyecto </b>:<b>&nbsp;</b>{project_status}<br><b>Presupuesto del proyecto </b>:<b> </b>{project_budget}<br><b>Horas del proyecto </b>:<b> </b>{project_hours}</p>',
                    'fr' => '<p>Bonjour,</p><p>Un nouveau projet vous a ??t?? attribu??.</p><p><b>nom du projet </b>: {project_name}<br><b>L\'??tat du projet </b>:<b>&nbsp;</b>{project_status}<br><b>Budget du projet </b>:<b> </b>{project_budget}<br><b>Heures du projet </b>:<b> </b>{project_hours}</p>',
                    'it' => '<p>Ciao,</p><p>Nuovo progetto ti ?? stato assegnato.</p><p><b>Nome del progetto </b>: {project_name}<br><b>Stato del progetto </b>:<b>&nbsp;</b>{project_status}<br><b>Budget del progetto </b>:<b> </b>{project_budget}<br><b>Ore del progetto </b>:<b> </b>{project_hours}</p>',
                    'ja' => '<p>??????????????????</p><p>????????????????????????????????????????????????????????????</p><p><b>????????????????????? </b>: {project_name}<br><b>??????????????????????????? </b>:<b>&nbsp;</b>{project_status}<br><b>???????????????????????? </b>:<b> </b>{project_budget}<br><b>???????????????????????? </b>:<b> </b>{project_hours}</p>',
                    'nl' => '<p>Hallo,</p><p>Nieuw project is aan u toegewezen.</p><p><b>Naam van het project </b>: {project_name}<br><b>Project status </b>:<b>&nbsp;</b>{project_status}<br><b>Project budget </b>:<b> </b>{project_budget}<br><b>Projecturen </b>:<b> </b>{project_hours}</p>',
                    'pl' => '<p>Dzie?? dobry,</p><p>Nowy projekt zosta?? Ci przypisany.</p><p><b>Nazwa Projektu </b>: {project_name}<br><b>Stan projektu </b>:<b>&nbsp;</b>{project_status}<br><b>Bud??et projektu </b>:<b> </b>{project_budget}<br><b>Godziny projektu </b>:<b> </b>{project_hours}</p>',
                    'ru' => '<p>????????????,</p><p>?????????? ???????????? ?????? ???????????????? ??????.</p><p><b>???????????????? ?????????????? </b>: {project_name}<br><b>???????????? ?????????????? </b>:<b>&nbsp;</b>{project_status}<br><b>???????????? ?????????????? </b>:<b> </b>{project_budget}<br><b>???????? ???????????? ?????????????? </b>:<b> </b>{project_hours}</p>',
                ],
            ],
            'Task Assign' => [
                'subject' => 'New Task Assign',
                'lang' => [
                    'ar' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">????????????</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">???? ?????????? ???????? ?????????? ????.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>???????? </b><span style="font-weight: bolder;">??????</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">???????????? ????????????</span>&nbsp;: {task_priority}<br><b>?????????? ???????????? </b>: {task_project}<b>&nbsp;<br>?????????? ???????????? </b>: {task_stage}</span></p>',
                    'da' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">Hej,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">Ny opgave er blevet tildelt til dig.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>Opgave </b><span style="font-weight: bolder;">Navn</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">Opgaveprioritet</span>&nbsp;: {task_priority}<br><b>Opgaveprojekt </b>: {task_project}<b>&nbsp;<br>Opgavefase </b>: {task_stage}</span></p>',
                    'de' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">Hallo,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">Neue Aufgabe wurde Ihnen zugewiesen.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>Aufgabe </b><span style="font-weight: bolder;">Name</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">Aufgabenpriorit??t</span>&nbsp;: {task_priority}<br><b>Aufgabenprojekt </b>: {task_project}<b>&nbsp;<br>Aufgabenphase </b>: {task_stage}</span></p>',
                    'en' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">Hello,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">New Task has been Assign to you.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>Task </b><span style="font-weight: bolder;">Name</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">Task Priority</span>&nbsp;: {task_priority}<br><b>Task Project </b>: {task_project}<b>&nbsp;<br>Task Stage </b>: {task_stage}</span></p>',
                    'es' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">Hola,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">Nueva tarea ha sido asignada a usted.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>Tarea </b><span style="font-weight: bolder;">Nombre</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">Prioridad de tarea</span>&nbsp;: {task_priority}<br><b>Proyecto de tarea </b>: {task_project}<b>&nbsp;<br>Etapa de tarea </b>: {task_stage}</span></p>',
                    'fr' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">Bonjour,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">Une nouvelle t??che vous a ??t?? assign??e.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>T??che </b><span style="font-weight: bolder;">Nom</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">Priorit?? des t??ches</span>&nbsp;: {task_priority}<br><b>Projet de t??che </b>: {task_project}<b>&nbsp;<br>??tape de la t??che </b>: {task_stage}</span></p>',
                    'it' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">Ciao,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">La nuova attivit?? ?? stata assegnata a te.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>Compito </b><span style="font-weight: bolder;">Nome</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">Priorit?? dell\'attivit??</span>&nbsp;: {task_priority}<br><b>Progetto di attivit?? </b>: {task_project}<b>&nbsp;<br>Fase delle attivit?? </b>: {task_stage}</span></p>',
                    'ja' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">??????????????????</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">???????????????????????????????????????????????????</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>?????? </b><span style="font-weight: bolder;">??????</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">?????????????????????</span>&nbsp;: {task_priority}<br><b>??????????????????????????? </b>: {task_project}<b>&nbsp;<br>????????????????????? </b>: {task_stage}</span></p>',
                    'nl' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">Hallo,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">Nieuwe taak is aan u toegewezen.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>Taak </b><span style="font-weight: bolder;">Naam</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">Taakprioriteit</span>&nbsp;: {task_priority}<br><b>Taakproject </b>: {task_project}<b>&nbsp;<br>Taakfase </b>: {task_stage}</span></p>',
                    'pl' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">Dzie?? dobry,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">Nowe zadanie zosta??o Ci przypisane.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>Zadanie </b><span style="font-weight: bolder;">Imi??</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">Priorytet zadania</span>&nbsp;: {task_priority}<br><b>Projekt zadania </b>: {task_project}<b>&nbsp;<br>Etap zadania </b>: {task_stage}</span></p>',
                    'ru' => '<p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;">????????????,</span><br style="font-family: sans-serif;"><span style="font-family: sans-serif;">?????????? ???????????? ???????? ?????????????????? ??????.</span></p><p style="line-height: 28px; font-family: Nunito, &quot;Segoe UI&quot;, arial; font-size: 14px;"><span style="font-family: sans-serif;"><b>???????????? </b><span style="font-weight: bolder;">????????????????</span>&nbsp;: {task_name}<br><span style="font-weight: bolder;">?????????????????? ????????????</span>&nbsp;: {task_priority}<br><b>???????????? ???????????? </b>: {task_project}<b>&nbsp;<br>???????? ???????????? </b>: {task_stage}</span></p>',
                ],
            ],
            'Create Timesheet' => [
                'subject' => 'New Timesheet Assign',
                'lang' => [
                    'ar' => '<p><span style="font-size: 14px; font-family: sans-serif;">????????????</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">???? ?????????? ???????? ???????? ???????? ????.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>?????????? ???????????? ????????????</b> : {timesheet_project}<br><b>???????? ???????????? ????????????</b> : {timesheet_task}<br><b>?????? ???????????? ????????????</b> : {timesheet_time}<br><b>?????????? ???????????? ????????????</b> : {timesheet_date}</span></p><p><br></p>',
                    'da' => '<p><span style="font-size: 14px; font-family: sans-serif;">Hej,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">Nyt timesheet er blevet tildelt til dig.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>Timesheet-projekt</b> : {timesheet_project}<br><b>Timesheet-opgave</b> : {timesheet_task}<br><b>Tidsskema Tid</b> : {timesheet_time}<br><b>Tidspunkt Dato</b> : {timesheet_date}</span></p><p><br></p>',
                    'de' => '<p><span style="font-size: 14px; font-family: sans-serif;">Hallo,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">Neue Arbeitszeittabelle wurde Ihnen zugewiesen.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>Arbeitszeittabellenprojekt</b> : {timesheet_project}<br><b>Arbeitszeittabellenaufgabe</b> : {timesheet_task}<br><b>Arbeitszeittabelle Zeit</b> : {timesheet_time}<br><b>Arbeitszeittabelle Datum</b> : {timesheet_date}</span></p><p><br></p>',
                    'en' => '<p><span style="font-size: 14px; font-family: sans-serif;">Hello,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">New Timesheet has been Assign to you.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>Timesheet Project</b> : {timesheet_project}<br><b>Timesheet Task</b> : {timesheet_task}<br><b>Timesheet Time</b> : {timesheet_time}<br><b>Timesheet Date</b> : {timesheet_date}</span></p><p><br></p>',
                    'es' => '<p><span style="font-size: 14px; font-family: sans-serif;">Hola,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">Se le ha asignado una nueva hoja de tiempo.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>Proyecto de parte de horas</b> : {timesheet_project}<br><b>Tarea de parte de horas</b> : {timesheet_task}<br><b>Tiempo de parte de horas</b> : {timesheet_time}<br><b>Fecha de parte de horas</b> : {timesheet_date}</span></p><p><br></p>',
                    'fr' => '<p><span style="font-size: 14px; font-family: sans-serif;">Bonjour,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">Une nouvelle feuille de temps vous a ??t?? attribu??e.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>Projet de feuille de temps</b> : {timesheet_project}<br><b>T??che de feuille de temps</b> : {timesheet_task}<br><b>Temps de feuille de temps</b> : {timesheet_time}<br><b>Date de la feuille de temps</b> : {timesheet_date}</span></p><p><br></p>',
                    'it' => '<p><span style="font-size: 14px; font-family: sans-serif;">Ciao,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">La nuova scheda attivit?? ?? stata assegnata a te.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>Progetto scheda attivit??</b> : {timesheet_project}<br><b>Attivit?? scheda attivit??</b> : {timesheet_task}<br><b>Timesheet Time</b> : {timesheet_time}<br><b>Data scheda attivit??</b> : {timesheet_date}</span></p><p><br></p>',
                    'ja' => '<p><span style="font-size: 14px; font-family: sans-serif;">??????????????????</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">????????????????????????????????????????????????????????????</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>????????????????????????????????????</b> : {timesheet_project}<br><b>???????????????????????????</b> : {timesheet_task}<br><b>????????????????????????</b> : {timesheet_time}<br><b>???????????????????????????</b> : {timesheet_date}</span></p><p><br></p>',
                    'nl' => '<p><span style="font-size: 14px; font-family: sans-serif;">Hallo,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">New Timesheet is aan u toewijzen.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>Timesheet Project</b> : {timesheet_project}<br><b>Timesheet-taak</b> : {timesheet_task}<br><b>Timesheet Time</b> : {timesheet_time}<br><b>Datum rooster</b> : {timesheet_date}</span></p><p><br></p>',
                    'pl' => '<p><span style="font-size: 14px; font-family: sans-serif;">Dzie?? dobry,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">Nowy grafik zosta?? przypisany do Ciebie.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>Projekt grafiku</b> : {timesheet_project}<br><b>Zadanie grafiku</b> : {timesheet_task}<br><b>Czas pracy</b> : {timesheet_time}<br><b>Data grafiku</b> : {timesheet_date}</span></p><p><br></p>',
                    'ru' => '<p><span style="font-size: 14px; font-family: sans-serif;">????????????,</span><br style="font-size: 14px; font-family: sans-serif;"><span style="font-size: 14px; font-family: sans-serif;">?????????? ???????????????????? ???????? ?????????????????? ??????.</span></p><p><span style="font-size: 14px; font-family: sans-serif;"><b>???????????? ????????????????????</b> : {timesheet_project}<br><b>???????????? ????????????????????</b> : {timesheet_task}<br><b>????????????????????</b> : {timesheet_time}<br><b>???????? ????????????????????</b> : {timesheet_date}</span></p><p><br></p>',
                ],
            ],
        ];

        $email = EmailTemplate::all();

        // Make entry in email_template_lang tbl
        foreach($email as $e)
        {
            foreach($defaultTemplate[$e->name]['lang'] as $lang => $content)
            {
                EmailTemplateLang::create([
                                              'parent_id' => $e->id,
                                              'lang' => $lang,
                                              'subject' => $defaultTemplate[$e->name]['subject'],
                                              'content' => $content,
                                          ]);
            }
        }
    }

    // Get All permission
    public function getAllPermission()
    {
        return [
            "create milestone",
            "edit milestone",
            "delete milestone",
            "create task",
            "edit task",
            "delete task",
            "show task",
            "move task",
            "create timesheet",
            "show as admin timesheet",
            "create expense",
            "show expense",
            "show activity",
            "project setting",
        ];
    }

    // Get project wise permission
    public function getPermission($project_id)
    {
        $data = ProjectUser::where('project_id', '=', $project_id)->where('user_id', '=', $this->id)->first();

        return json_decode($data->user_permission, true);
    }

    // check project is shared or not
    public function checkProject($project_id)
    {
        $user_projects = $this->projects()->pluck('permission', 'project_id')->toArray();
        if(array_key_exists($project_id, $user_projects))
        {
            $projectstatus = $user_projects[$project_id] == 'owner' ? 'Owner' : 'Shared';
        }

        return $projectstatus;
    }

    // Check Plan
    public function getPlan()
    {
        $user = \App\Models\User::find($this->id);

        return Plan::find($user->plan);
    }

    // for Assign plan
    public function assignPlan($planID, $frequency = '')
    {
        $usr  = $this;
        $plan = Plan::find($planID);

        if($plan)
        {
            if(\Auth::check())
            {
                $usr_contact = $usr->contacts->pluck('user_id')->toArray();

                if(count($usr_contact) > 0)
                {
                    $users     = User::whereIn('id', $usr_contact)->get();
                    $userCount = 0;

                    foreach($users as $user)
                    {
                        $userCount++;
                        $user->is_active = ($plan->max_users == -1 || $userCount <= $plan->max_users) ? 1 : 0;
                        $user->save();
                    }
                }

                $user_project = $usr->projects()->pluck('project_id')->toArray();

                if(count($user_project) > 0)
                {
                    $projects     = Project::whereIn('id', $user_project)->get();
                    $projectCount = 0;

                    foreach($projects as $project)
                    {
                        $projectCount++;
                        $project->is_active = ($plan->max_projects == -1 || $projectCount <= $plan->max_projects) ? 1 : 0;
                        $project->save();
                    }
                }
            }

            $this->plan = $plan->id;
            if($frequency == 'weekly')
            {
                $this->plan_expire_date = Carbon::now()->addWeeks(1)->isoFormat('YYYY-MM-DD');
            }
            elseif($frequency == 'monthly')
            {
                $this->plan_expire_date = Carbon::now()->addMonths(1)->isoFormat('YYYY-MM-DD');
            }
            elseif($frequency == 'annual')
            {
                $this->plan_expire_date = Carbon::now()->addYears(1)->isoFormat('YYYY-MM-DD');
            }
            else
            {
                $this->plan_expire_date = null;
            }
            $this->save();

            return ['is_success' => true];
        }
        else
        {
            return [
                'is_success' => false,
                'error' => __('Plan is deleted.'),
            ];
        }
    }

    // for get user role
    public function usrRole()
    {
        $usr = \Auth::user();
        if($usr->id != $this->id)
        {
            $role      = UserContact::where('parent_id', '=', $usr->id)->where('user_id', '=', $this->id)->first();
            $arrReturn = [
                'color' => ($role->role == 'user') ? 'primary' : 'warning',
                'role' => ucfirst($role->role),
            ];
        }
        else
        {
            $arrReturn = [
                'color' => 'success',
                'role' => 'User',
            ];
        }


        return $arrReturn;
    }

    // get user's created taxes
    public function taxes()
    {
        return $this->hasMany('App\Models\Tax', 'created_by', 'id');
    }

    // get decoded details
    public function decodeDetails($user_id = '')
    {
        $arr = [
            'light_logo' => '',
            'dark_logo' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'zipcode' => '',
            'country' => '',
            'telephone' => '',
            'invoice_template' => 'template1',
            'invoice_color' => 'ffffff',
            'invoice_logo' => '',
            'invoice_footer_title' => '',
            'invoice_footer_note' => '',
            'interval_time'=>''
        ];

        if(empty($user_id))
        {
            $data = json_decode($this->details, true);
        }
        else
        {
            $usr  = User::find($user_id);
            $data = json_decode($usr->details, true);
        }

        if(!empty($data))
        {
            foreach($arr as $key => $val)
            {
                $arr[$key] = (!empty($data[$key])) ? $data[$key] : $arr[$key];
            }
        }

        $arr['light_logo']   = empty($arr['light_logo']) ? 'logo/logo.png' : $arr['light_logo'];
        $arr['dark_logo']    = empty($arr['dark_logo']) ? 'logo/logo.png' : $arr['dark_logo'];
        $arr['invoice_logo'] = empty($arr['invoice_logo']) ? 'logo/logo.png' : $arr['invoice_logo'];


        return $arr;
    }

    public function cancel_subscription($user_id = false)
    {
        $user = User::find($user_id);

        if(!$user_id && !$user && $user->payment_subscription_id != '' && $user->payment_subscription_id != null)
        {
            return true;
        }

        $data            = explode('###', $user->payment_subscription_id);
        $type            = strtolower($data[0]);
        $subscription_id = $data[1];

        $paymentSetting = Utility::getPaymentSetting();

        switch($type)
        {
            case 'stripe':

                /* Initiate Stripe */ \Stripe\Stripe::setApiKey($paymentSetting['stripe_secret']);

                /* Cancel the Stripe Subscription */
                $subscription = \Stripe\Subscription::retrieve($subscription_id);
                $subscription->cancel();

                break;

            case 'paypal':

                /* Initiate paypal */ $paypal = new \PayPal\Rest\ApiContext(new \PayPal\Auth\OAuthTokenCredential($paymentSetting['paypal_client_id'], $paymentSetting['paypal_secret_key']));
                $paypal->setConfig(['mode' => $paymentSetting['paypal_mode']]);

                /* Create an Agreement State Descriptor, explaining the reason to suspend. */
                $agreement_state_descriptior = new \PayPal\Api\AgreementStateDescriptor();
                $agreement_state_descriptior->setNote('Suspending the agreement');

                /* Get details about the executed agreement */
                $agreement = \PayPal\Api\Agreement::get($subscription_id, $paypal);

                /* Suspend */
                $agreement->suspend($agreement_state_descriptior, $paypal);

                break;
        }

        $user->payment_subscription_id = '';
        $user->save();
    }

    public static function invoiceNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public static function dateFormat($date)
    {
        $settings = Utility::settings();

        return date($settings['site_date_format'], strtotime($date));
    }

    public static function priceFormat($price)
    {
        $settings = Utility::settings();

        return (($settings['site_currency_symbol_position'] == "pre") ? $settings['site_currency_symbol'] : '') . number_format($price, 2) . (($settings['site_currency_symbol_position'] == "post") ? $settings['site_currency_symbol'] : '');
    }
    public function projectsList(){
        $user_projects = $this->projects()->pluck('project_id')->toArray();
        $project = Project::select('projects.id','projects.name')->with('tasks')->whereIn('id',$user_projects)->get()->toArray();
        return $project;
    }

    public function timeFormat($time)
    {
        $settings = Utility::settings();

        return date($settings['site_time_format'], strtotime($time));
    }
}
