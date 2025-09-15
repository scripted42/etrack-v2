// icons
import {
  DashboardOutlined,
  UserOutlined,
  TeamOutlined,
  FileTextOutlined,
  HistoryOutlined,
  SettingOutlined,
  LogoutOutlined,
  BookOutlined,
  BankOutlined
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
  { header: 'Pengaturan' },
  {
    title: 'Profil',
    icon: UserOutlined,
    to: '/profile'
  },
  {
    title: 'Pengaturan',
    icon: SettingOutlined,
    to: '/settings'
  }
];

export default sidebarItem;
