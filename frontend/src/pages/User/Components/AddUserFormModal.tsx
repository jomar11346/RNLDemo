import { useEffect, useState, type FC, type FormEvent } from "react";
import FloatingLabelInput from "../../../components/Input/FloatingLabelInput";
import Modal from "../../../components/Modal";
import FloatingLabelSelect from "../../../components/Select/FloatingLabelSelect";
import SubmitButton from "../../../components/Button/SubmitButton";
import CloseButton from "../../../components/Button/CloseButton";
import type { GenderColumns } from "../../../interfaces/GenderColumns";
import GenderService from "../../../Services/GenderService";
import type { UserFieldErrors } from "../../../interfaces/UsersFieldError";
import UserService from "../../../Services/UserService";

interface AddUserFormModalProps {
    onUserAdded: (message: string) => void;
    refreshKey?: () => void;
    isOpen: boolean;
    onClose: () => void;
}

const AddUserFormModal: FC<AddUserFormModalProps> = ({ 
    onUserAdded, 
    refreshKey,
    isOpen, 
    onClose
}) => {
    const [loadingGenders, setLoadingGenders] = useState(false);
    const [genders, setGenders] = useState<GenderColumns[]>([]);

    const [loadingStore, setLoadingStore] = useState(false);
    const [firstName, setFirstName] = useState("");
    const [middleName, setMiddleName] = useState("");
    const [lastName, setLastName] = useState("");
    const [suffixName, setSuffixName] = useState("");
    const [gender, setGender] = useState("");
    const [birthdate, setBirthDate] = useState("");
    const [username, setUsername] = useState("");
    const [password, setPassword] = useState("");
    const [passwordConfirmation, setPasswordConfirmation] = useState("");
    const [errors, setErrors] = useState<UserFieldErrors>({});

    const handleStoreUser = async (e: FormEvent) => {
        try {
            e.preventDefault();
            
            setLoadingStore(true);

            const payload = {
                first_name: firstName,
                middle_name: middleName,
                last_name: lastName,
                suffix_name: suffixName,
                gender,
                birth_date: birthdate,
                username,
                password,
                password_confirmation: passwordConfirmation,
            };

            const res = await UserService.storeUser(payload);

            if (res.status === 200) {
             onUserAdded(res.data.message);
             
             setFirstName("");
             setMiddleName("");
             setLastName("");
             setSuffixName("");
             setGender("");
             setBirthDate("");
             setUsername("");
             setPassword("");
             setPasswordConfirmation("");
             setErrors({});
             
             refreshKey?.();
             handleLoadGenders();
            } else {
                console.error(
                    "Unexpected status error occurred during adding user:", 
                    res.status
                );
            }
        } catch (error: any) {
            if (error.response && error.response.status === 422) {
                setErrors(error.response.data?.errors);
            } else {
                console.log(
                    "Unexpected server error occurred during adding user:", 
                    error
                );
            }
        } finally {
            setLoadingStore(false);
        }
    };

    const handleLoadGenders = async () => {
        try {
            setLoadingGenders(true);

            const res = await GenderService.loadGenders();

            if (res.status === 200) {
                setGenders(res.data.genders);
            } else {
                console.error(
                    "Unexpected error status occurred during loading genders:", 
                    res.status
                );
            }
        } catch (error) {
            console.error(
                "Unexpected error occurred during loading genders:", 
                error
            );
        } finally {
            setLoadingGenders(false);
        }
    };

    useEffect(() => {
        if (isOpen) {
            handleLoadGenders();
        }

    }, [isOpen]);

    return (
        <>
         <Modal isOpen={isOpen} onClose={onClose} showCloseButton>
            <form onSubmit={handleStoreUser}>
                <h1 className="text-2xl border-b border-gray-100 p-4 font-semibold mb-4">
                    Add User Form
                </h1>
                <div className="grid grid-cols-2 gap-4 border-b border-gray-100 mb-4">
                    <div className="col-span-2 md:col-span-1">
                        <div className="mb-4">
                            <FloatingLabelInput
                                label="First Name"
                                type="text"
                                name="first_name"
                                value={firstName}
                                onChange={(e) => setFirstName(e.target.value)}
                                required
                                autoFocus
                                errors={errors.first_name}
                            />
                        </div>
                        <div className="mb-4">
                            <FloatingLabelInput
                                label="Middle Name"
                                type="text"
                                name="middle_name"
                                value={middleName}
                                onChange={(e) => setMiddleName(e.target.value)}
                                errors={errors.middle_name}
                            />
                        </div>
                        <div className="mb-4">
                            <FloatingLabelInput
                                label="Last Name"
                                type="text"
                                name="last_name"
                                value={lastName}
                                onChange={(e) => setLastName(e.target.value)}
                                required
                                errors={errors.last_name}
                            />
                        </div>
                        <div className="mb-4">
                            <FloatingLabelInput
                                label="Suffix Name"
                                type="text"
                                name="suffix_name"
                                value={suffixName}
                                onChange={(e) => setSuffixName(e.target.value)}
                                errors={errors.suffix_name}
                            />
                        </div>
                        <div className="mb-4">
                            <FloatingLabelSelect
                                label="Gender"
                                name="gender"
                                value={gender}
                                onChange={(e) => setGender(e.target.value)}
                                required
                                errors={errors.gender}
                            >
                                <option value="">Select Gender</option>
                                {loadingGenders ? (
                                    <option value="" disabled>
                                        Loading...
                                    </option>
                                ) : (
                                    <>
                                        {genders.map((genderItem) => (
                                            <option value={String(genderItem.gender_id)} key={genderItem.gender_id}>
                                                {genderItem.gender}
                                            </option>
                                        ))}
                                    </>
                                )}
                            </FloatingLabelSelect>
                        </div>
                    </div>
                    <div className="col-span-2 md:col-span-1">
                        <div className="mb-4">
                            <FloatingLabelInput
                                label="Birth Date"
                                type="date"
                                name="birth_date"
                                value={birthdate}
                                onChange={(e) => setBirthDate(e.target.value)}
                                required
                                errors={errors.birth_date}
                            />
                        </div>
                        <div className="mb-4">
                            <FloatingLabelInput
                                label="Username"
                                type="text"
                                name="username"
                                value={username}
                                onChange={(e) => setUsername(e.target.value)}
                                required
                                errors={errors.username}
                            />
                        </div>
                        <div className="mb-4">
                            <FloatingLabelInput
                                label="Password"
                                type="password"
                                name="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                                errors={errors.password}
                            />
                        </div>
                        <div className="mb-4">
                            <FloatingLabelInput
                                label="Password Confirmation"
                                type="password"
                                name="password_confirmation"
                                value={passwordConfirmation}
                                onChange={(e) => setPasswordConfirmation(e.target.value)}
                                required
                                errors={errors.password_confirmation}
                            />
                        </div>
                    </div>
                </div>
                <div className="flex justify-end gap-2">
                    {!loadingStore && (
                        <CloseButton label="Close" onClose={onClose} />
                    )}
                    <SubmitButton label="Save User" loading={loadingStore} loadingLabel="Saving User..." />
                </div>
            </form>
         </Modal>
        </>
    );
};

export default AddUserFormModal;