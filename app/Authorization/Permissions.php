<?php

namespace App\Authorization;

final class Permissions
{
    public const DASHBOARD_ACCESS = 'dashboard.access';

    public const BENEFICIARIES_VIEW = 'beneficiaries.view';

    public const BENEFICIARIES_MANAGE = 'beneficiaries.manage';

    public const PROJECTS_VIEW = 'projects.view';

    public const PROJECTS_MANAGE = 'projects.manage';

    public const TRAININGS_VIEW = 'trainings.view';

    public const TRAININGS_MANAGE = 'trainings.manage';

    public const ASSISTANCE_VIEW = 'assistance.view';

    public const ASSISTANCE_MANAGE = 'assistance.manage';

    public const GROUPS_VIEW = 'groups.view';

    public const GROUPS_MANAGE = 'groups.manage';

    public const AUDIT_LOGS_VIEW = 'audit_logs.view';

    public const REPORTS_EXPORT = 'reports.export';

    public const NOTIFICATIONS_ACCESS = 'notifications.access';

    public const USERS_MANAGE = 'users.manage';

    public const ROLES_MANAGE = 'roles.manage';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DASHBOARD_ACCESS,
            self::BENEFICIARIES_VIEW,
            self::BENEFICIARIES_MANAGE,
            self::PROJECTS_VIEW,
            self::PROJECTS_MANAGE,
            self::TRAININGS_VIEW,
            self::TRAININGS_MANAGE,
            self::ASSISTANCE_VIEW,
            self::ASSISTANCE_MANAGE,
            self::GROUPS_VIEW,
            self::GROUPS_MANAGE,
            self::AUDIT_LOGS_VIEW,
            self::REPORTS_EXPORT,
            self::NOTIFICATIONS_ACCESS,
            self::USERS_MANAGE,
            self::ROLES_MANAGE,
        ];
    }
}
