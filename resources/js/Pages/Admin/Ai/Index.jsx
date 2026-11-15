import { Link } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import AiGeneratorWorkspace from '@/cms/admin/AiGeneratorWorkspace';

export default function AiGeneratorIndex(props) {
    return (
        <AdminLayout
            title="AI Website Generator"
            subtitle="Generate a multi-page website, add a single landing page, or modify and redesign any existing page."
            actions={
                <>
                    <Link className="btn btn-light" href={route('admin.pages.index')}>
                        All pages
                    </Link>
                    <Link className="btn btn-light" href={props.settingsUrl}>
                        AI API settings
                    </Link>
                </>
            }
        >
            <AiGeneratorWorkspace {...props} />
        </AdminLayout>
    );
}
