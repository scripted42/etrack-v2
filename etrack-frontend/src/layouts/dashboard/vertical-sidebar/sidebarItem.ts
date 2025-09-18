// icons
import {
  DashboardOutlined,
  UserOutlined,
  TeamOutlined,
  FileTextOutlined,
  HistoryOutlined,
  CloudUploadOutlined,
  SettingOutlined,
  LogoutOutlined,
  BookOutlined,
  BankOutlined,
  KeyOutlined,
  SafetyOutlined,
  ExportOutlined,
  CameraOutlined,
} from '@ant-design/icons-vue';

export interface menu {
  header?: string;
  title?: string;
  icon?: object;
  to?: string;
  divider?: boolean;
  chip?: string;
  chipColor?: string;
  chipVariant?: string;
  chipIcon?: string;
  children?: menu[];
  disabled?: boolean;
  type?: string;
  subCaption?: string;
  permission?: string;
}

const sidebarItem: menu[] = [
  { header: 'Menu Utama' },
  {
    title: 'Dashboard',
    icon: DashboardOutlined,
    to: '/dashboard',
    permission: 'view_dashboard'
  },
  { header: 'Absensi' },
  {
    title: 'Absensi Wajah',
    icon: CameraOutlined,
    to: '/face-attendance',
    permission: 'attendance_access'
  },
  {
    title: 'Registrasi Wajah',
    icon: CameraOutlined,
    to: '/face-registration',
    permission: 'manage_employees'
  },
  {
    title: 'Riwayat Absensi Wajah',
    icon: HistoryOutlined,
    to: '/face-attendance-history',
    permission: 'view_attendance_history'
  },
  { header: 'Manajemen Data' },
  {
    title: 'Data Siswa',
    icon: BookOutlined,
    to: '/students',
    permission: 'manage_students'
  },
  {
    title: 'Data Pegawai',
    icon: BankOutlined,
    to: '/employees',
    permission: 'manage_employees'
  },
  {
    title: 'Manajemen User',
    icon: UserOutlined,
    to: '/users',
    permission: 'manage_users'
  },
  { header: 'Laporan & Monitoring' },
  {
    title: 'Laporan',
    icon: FileTextOutlined,
    to: '/reports',
    permission: 'view_reports'
  },
    {
      title: 'Audit Log',
      icon: HistoryOutlined,
      to: '/audit-logs',
      permission: 'view_audit_logs'
    },
    {
      title: 'Backup & Restore',
      icon: CloudUploadOutlined,
      to: '/backup',
      permission: 'view_backups'
    },
       {
         title: 'Export Data',
         icon: ExportOutlined,
         to: '/export-data',
         permission: 'view_reports'
       },
    {
      title: 'Role Management',
      icon: SafetyOutlined,
      to: '/role-management',
      permission: 'manage_roles'
    },
  { header: 'Pengaturan' },
  {
    title: 'Profil',
    icon: UserOutlined,
    to: '/profile'
  },
  {
    title: 'Ubah Password',
    icon: KeyOutlined,
    to: '/change-password'
  },
  {
    title: 'Pengaturan',
    icon: SettingOutlined,
    to: '/settings'
  }
];

export default sidebarItem;
