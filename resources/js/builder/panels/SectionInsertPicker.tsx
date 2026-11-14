import { Modal } from '@shared/ui/Modal';
import { Button } from '@shared/ui/Button';
import { createSection, type SectionData } from '@renderer';
import { DesignBlocks } from './DesignBlocks';

export function SectionInsertPicker({ onClose, onInsert }: {
    onClose: () => void;
    onInsert: (sections: SectionData[]) => void;
}) {
    return <Modal open onClose={onClose} title="Insert a section" size="xl"
        description="Choose a ready-made design or start blank. It will be inserted at the position you selected.">
        <div className="flex flex-col gap-4">
            <Button variant="outline" onClick={() => onInsert([createSection('New section')])}>+ Blank section</Button>
            <div className="h-[60vh] min-h-0">
                <DesignBlocks presentation="grid" onInsert={(block) => onInsert(block.sections)} />
            </div>
        </div>
    </Modal>;
}
